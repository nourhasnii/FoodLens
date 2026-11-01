<?php

namespace App\Console\Commands;

use App\Services\NutriAlertService;
use Illuminate\Console\Command;

class ScanRiskAlerts extends Command
{
    protected $signature = 'nutritrace:scan-risks';

    protected $description = 'Détecte les risques (certifications, OCR, rappels, qualité, couverture) et synchronise le NutriAlert Hub';

    public function handle(NutriAlertService $service): int
    {
        $stats = $service->sync();

        $this->info(sprintf(
            'NutriAlert Hub — %d créée(s), %d mise(s) à jour, %d résolue(s), %d notification(s).',
            $stats['created'],
            $stats['updated'],
            $stats['resolved'],
            $stats['notified']
        ));

        return self::SUCCESS;
    }
}
