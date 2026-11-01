<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Accounts created before email verification was introduced were treated as
     * verified by the previous registration flow. Backfill them so enabling the
     * "verified" middleware (MustVerifyEmail) does not lock existing users out.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        // Irreversible data backfill: nothing to roll back.
    }
};
