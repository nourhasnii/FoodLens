<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('batches', 'qr_code_path')) {
            return;
        }

        Schema::table('batches', function (Blueprint $table) {
            $table->string('qr_code_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('qr_code_path');
        });
    }
};