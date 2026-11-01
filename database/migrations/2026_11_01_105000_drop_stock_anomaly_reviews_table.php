<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * La détection d'anomalies, essayée puis remplacée par la DLC dynamique, n'est pas livrée.
 * Si sa table a été créée sur un poste de test, elle est supprimée (sans effet ailleurs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('stock_anomaly_reviews');
    }

    public function down(): void
    {
        // Rien à recréer.
    }
};
