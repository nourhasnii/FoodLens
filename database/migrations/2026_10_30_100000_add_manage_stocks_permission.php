<?php

use App\Support\StockAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

/**
 * Module Stocks : crée la permission manage_stocks et la donne
 * à admin, producteur, transformateur et distributeur.
 * (Migration de données : une base déjà remplie est mise à jour avec un simple `php artisan migrate`.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        StockAccess::install();
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            Permission::where('name', StockAccess::PERMISSION)->delete();
        }
    }
};
