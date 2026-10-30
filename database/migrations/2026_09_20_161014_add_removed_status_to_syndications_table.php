
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // SQLite does not support MODIFY or MySQL ENUM.
        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("
                ALTER TABLE syndications
                DROP CONSTRAINT IF EXISTS syndications_status_check
            ");

            DB::statement("
                ALTER TABLE syndications
                ADD CONSTRAINT syndications_status_check
                CHECK (status IN (
                    'pending',
                    'approved',
                    'active',
                    'paused',
                    'revoked',
                    'removed'
                ))
            ");

            return;
        }

        if ($driver === 'mysql') {
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
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("
                ALTER TABLE syndications
                DROP CONSTRAINT IF EXISTS syndications_status_check
            ");

            DB::statement("
                ALTER TABLE syndications
                ADD CONSTRAINT syndications_status_check
                CHECK (status IN (
                    'pending',
                    'approved',
                    'active',
                    'paused',
                    'revoked'
                ))
            ");

            return;
        }

        if ($driver === 'mysql') {
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
    }
};
