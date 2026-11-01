<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Champs requis par le NutriAlert Hub :
 * - `dedup_key` : clé déterministe (type:id) garantissant l'idempotence
 *   (un risque => une alerte, mise à jour au lieu d'être dupliquée).
 * - `alertable` : lien polymorphe vers l'enregistrement source
 *   (Certification, OcrCheck, QualityCheck, Batch, Product) pour l'action directe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->string('dedup_key')->nullable()->unique();
            $table->nullableMorphs('alertable');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropUnique(['dedup_key']);
            $table->dropColumn('dedup_key');
            $table->dropMorphs('alertable');
        });
    }
};
