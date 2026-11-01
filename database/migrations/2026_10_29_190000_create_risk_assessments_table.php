<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique des évaluations NutriMind.
 *
 * Chaque évaluation est conservée (jamais écrasée) : entité évaluée, score,
 * sévérité, constatations détaillées, références de preuve, recommandation,
 * version du jeu de règles et horodatage. Permet la comparaison avant/après
 * une action corrective et la traçabilité des simulations (is_simulation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('assessable');
            $table->string('entity_label')->nullable();
            $table->decimal('score', 5, 2)->default(0);
            $table->string('severity')->default('info');
            $table->json('findings')->nullable();
            $table->json('evidence')->nullable();
            $table->text('recommendation')->nullable();
            $table->decimal('data_completeness', 5, 2)->default(100);
            $table->boolean('critical_override')->default(false);
            $table->string('rule_set_version')->default('1.0');
            $table->boolean('is_simulation')->default(false);
            $table->foreignId('baseline_id')->nullable()->constrained('risk_assessments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['assessable_type', 'assessable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};
