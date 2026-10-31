<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventaire par photo : une photo de rayon ou de palette, le comptage proposé par l'IA de vision,
 * puis le comptage validé par l'utilisateur, transformé en mouvements d'ajustement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('photo_path');
            $table->string('status', 20)->default('pending'); // pending, analyzed, failed, applied
            $table->string('ai_model')->nullable();
            $table->json('items')->nullable();      // comptage par produit (stock enregistré, IA, retenu)
            $table->json('unmatched')->nullable();  // objets vus par l'IA mais absents du stock du site
            $table->text('ai_notes')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedSmallInteger('adjustments_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_inventories');
    }
};
