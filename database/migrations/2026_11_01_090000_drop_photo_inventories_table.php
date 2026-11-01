<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * L'inventaire par photo (IA de vision via Ollama) a été retiré du module :
 * il dépendait d'un modèle de plusieurs Go à installer sur chaque PC.
 * Cette migration supprime sa table là où elle avait déjà été créée (sans effet ailleurs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('photo_inventories');
    }

    public function down(): void
    {
        // Rien à recréer : la fonctionnalité n'existe plus.
    }
};
