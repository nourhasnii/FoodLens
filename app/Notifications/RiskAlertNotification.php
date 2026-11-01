<?php

namespace App\Notifications;

use App\Models\Alert;
use App\Services\NutriAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RiskAlertNotification extends Notification
{
    use Queueable;

    public function __construct(public Alert $alert)
    {
        $this->alert->loadMissing(['alertable', 'batch.product']);
    }

    /**
     * Notification en base systématique ; e-mail pour les alertes critiques uniquement,
     * et seulement si la livraison e-mail est activée (config nutrialert.mail).
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->alert->severity === 'critical' && config('nutrialert.mail')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $action = app(NutriAlertService::class)->action($this->alert);

        $message = (new MailMessage)
            ->subject('Alerte critique NutriTrace — '.$this->alert->title)
            ->greeting('Alerte critique NutriTrace')
            ->line($this->alert->message)
            ->line('Score de priorité : '.rtrim(rtrim((string) $this->alert->risk_score, '0'), '.').' / 100');

        if ($action) {
            $message->action($action['label'], $action['url']);
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $action = app(NutriAlertService::class)->action($this->alert);

        return [
            'alert_id' => $this->alert->id,
            'title' => $this->alert->title,
            'message' => $this->alert->message,
            'severity' => $this->alert->severity,
            'source' => $this->alert->type,
            'priority' => (float) $this->alert->risk_score,
            'batch_id' => $this->alert->batch_id,
            'action_url' => $action['url'] ?? null,
            'action_label' => $action['label'] ?? null,
        ];
    }
}
