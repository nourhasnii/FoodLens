<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DLC dynamique : décision d'un responsable sur la DLC réelle prédite d'un lot
 * (validée → le module l'utilise ; rejetée → le module garde la DLC imprimée).
 * La prédiction est recopiée au moment de la décision, pour l'historique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelf_life_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->date('printed_expiry');
            $table->date('predicted_expiry');
            $table->date('predicted_earliest');   // fourchette pessimiste
            $table->date('predicted_latest');     // fourchette optimiste
            $table->decimal('days_lost', 6, 2);
            $table->string('risk', 20);           // ok, attention, critical
            $table->string('decision', 20);       // accepted, rejected
            $table->text('note')->nullable();
            $table->json('model');                // profil et paramètres utilisés
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['batch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelf_life_assessments');
    }
};
