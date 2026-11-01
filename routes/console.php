<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\BrevoMailService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('nutritrace:test-email {recipient?}', function (BrevoMailService $mail) {
    $recipient = $this->argument('recipient') ?: config('services.brevo.admin_email');

    if (blank($recipient)) {
        $this->error('FAILED: No recipient provided and ADMIN_NOTIFICATION_EMAIL is not configured.');
        return 1;
    }

    try {
        $mail->sendTestEmail($recipient);
        $this->info('SUCCESS:');
        $this->info('Brevo test email sent successfully.');
    } catch (\RuntimeException $exception) {
        $this->error('FAILED:');
        $this->error('Brevo email failed.');
        $this->error('HTTP status: ' . ($exception->getCode() ?: 'N/A'));
        $this->error('Message: ' . $exception->getMessage());
        return 1;
    }
})->purpose('Send a NutriTrace test email through Brevo');

// NutriAlert Hub : détection automatique des risques toutes les heures.
Schedule::command('nutritrace:scan-risks')->hourly()->withoutOverlapping();
