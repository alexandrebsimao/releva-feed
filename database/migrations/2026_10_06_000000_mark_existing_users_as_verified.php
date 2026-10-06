<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * E-mail verification became mandatory after these accounts were created.
     * Mark them as verified so existing users (including admins) keep access.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Irreversible on purpose: we cannot tell which users were verified before.
    }
};
