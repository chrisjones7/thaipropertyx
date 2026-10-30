<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE properties
            MODIFY status ENUM(
                'draft',
                'pending',
                'active',
                'paused',
                'sold',
                'rented',
                'withdrawn',
                'expired'
            ) NOT NULL DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE properties
            MODIFY status ENUM(
                'draft',
                'pending',
                'active',
                'sold',
                'rented',
                'withdrawn',
                'expired'
            ) NOT NULL DEFAULT 'draft'
        ");
    }
};