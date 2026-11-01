<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;

/**
 * Sends Laravel mail through the existing Brevo transactional HTTP API
 * (the same integration used by App\Services\BrevoMailService).
 */
class BrevoTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        $apiKey = config('services.brevo.api_key');
        $endpoint = config('services.brevo.endpoint', 'https://api.brevo.com/v3/smtp/email');

        if (blank($apiKey)) {
            throw new RuntimeException('Brevo email delivery is not configured: BREVO_API_KEY is missing.');
        }

        $email = $this->toEmail($message);

        // Reuse the sender configured for the existing Brevo integration so
        // transactional mail comes from a verified Brevo sender address.
        $from = $email->getFrom()[0] ?? null;
        $senderEmail = config('services.brevo.sender_email') ?: ($from?->getAddress() ?? config('mail.from.address'));
        $senderName = config('services.brevo.sender_name') ?: ($from?->getName() ?? config('mail.from.name'));

        $payload = [
            'sender' => array_filter(['email' => $senderEmail, 'name' => $senderName]),
            'to' => array_map(fn (Address $address) => array_filter([
                'email' => $address->getAddress(),
                'name' => $address->getName(),
            ]), $email->getTo()),
            'subject' => $email->getSubject() ?? '',
            'htmlContent' => $email->getHtmlBody() ?? nl2br((string) $email->getTextBody()),
        ];

        if ($cc = $email->getCc()) {
            $payload['cc'] = array_map(fn (Address $address) => array_filter([
                'email' => $address->getAddress(),
                'name' => $address->getName(),
            ]), $cc);
        }

        if ($replyTo = $email->getReplyTo()) {
            $payload['replyTo'] = array_filter([
                'email' => $replyTo[0]->getAddress(),
                'name' => $replyTo[0]->getName(),
            ]);
        }

        $response = Http::withHeaders([
            'api-key' => $apiKey,
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ])->timeout((int) config('services.brevo.timeout', 15))->post($endpoint, $payload);

        if ($response->failed()) {
            Log::error('Brevo transactional email failed.', [
                'status' => $response->status(),
                'message' => $response->json('message'),
            ]);

            throw new RuntimeException(sprintf(
                'Brevo rejected the email request. HTTP status: %d. Message: %s',
                $response->status(),
                (string) ($response->json('message') ?? 'Brevo returned an error without a message.'),
            ), $response->status());
        }

        Log::info('Brevo transactional email accepted.', [
            'message_id' => (string) ($response->json('messageId') ?? 'accepted'),
        ]);
    }

    private function toEmail(SentMessage $message): Email
    {
        $original = $message->getOriginalMessage();

        if ($original instanceof Email) {
            return $original;
        }

        if ($original instanceof Message) {
            return MessageConverter::toEmail($original);
        }

        throw new RuntimeException('The message could not be converted to an email for Brevo delivery.');
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
