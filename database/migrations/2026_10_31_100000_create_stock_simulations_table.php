<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jumeau numérique « Et si ? » : chaque simulation est gardée avec son scénario et ses résultats,
 * pour la revoir ou la comparer plus tard (les stocks réels ne sont jamais modifiés).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_simulations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('preset', 30)->default('custom');
            $table->json('scenario');
            $table->unsignedSmallInteger('horizon_days');
            $table->date('start_date');
            // Indicateurs clés des deux stratégies (affichés dans l'historique sans relire tout le résultat)
            $table->decimal('baseline_wasted', 12, 2)->default(0);
            $table->decimal('ai_wasted', 12, 2)->default(0);
            $table->decimal('baseline_unmet', 12, 2)->default(0);
            $table->decimal('ai_unmet', 12, 2)->default(0);
            $table->decimal('ai_donated', 12, 2)->default(0);
            $table->longText('results');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_simulations');
    }
};
