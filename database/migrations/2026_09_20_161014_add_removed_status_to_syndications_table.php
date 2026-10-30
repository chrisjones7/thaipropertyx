<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE syndications
            MODIFY status ENUM(
                'pending',
                'approved',
                'active',
                'paused',
                'revoked',
                'removed'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE syndications
            MODIFY status ENUM(
                'pending',
                'approved',
                'active',
                'paused',
                'revoked'
            ) NOT NULL DEFAULT 'pending'
        ");
    }
};