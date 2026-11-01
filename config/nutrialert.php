<?php

/*
|--------------------------------------------------------------------------
| NutriAlert Hub — Détection automatique des risques
|--------------------------------------------------------------------------
| Seuils déterministes utilisés par App\Services\NutriAlertService.
| Aucune donnée inventée : chaque règle s'appuie sur une colonne existante
| des tables certifications, ocr_checks, batches, quality_checks, products.
*/

return [

    // Jours avant expiration d'une certification qui déclenchent une alerte.
    'certification_warning_days' => [15, 7, 3],

    // Score d'incohérence OCR (0-100) à partir duquel une alerte est créée.
    'ocr_threshold' => 40,
    'ocr_critical_threshold' => 70,

    // Score qualité (0-100) en dessous duquel une alerte est créée.
    'low_quality_threshold' => 50,
    'low_quality_critical_threshold' => 30,

    // Notification e-mail (transport mail existant) réservée aux alertes critiques.
    // Réutilise STOCK_ALERT_MAIL pour rester cohérent avec le module Stocks.
    'mail' => env('NUTRIALERT_MAIL', env('STOCK_ALERT_MAIL', false)),

];
