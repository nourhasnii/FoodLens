<?php

namespace Database\Seeders;

use App\Support\StockAccess;
use Illuminate\Database\Seeder;

/** Permission manage_stocks du module Stocks (rejouable sans risque). */
class StockPermissionSeeder extends Seeder
{
    public function run(): void
    {
        StockAccess::install();
    }
}
