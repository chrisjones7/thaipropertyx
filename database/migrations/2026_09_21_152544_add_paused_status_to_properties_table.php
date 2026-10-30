
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE properties
                DROP CONSTRAINT IF EXISTS properties_status_check
            ");

            DB::statement("
                ALTER TABLE properties
                ADD CONSTRAINT properties_status_check
                CHECK (status IN (
                    'draft',
                    'pending',
                    'active',
                    'paused',
                    'sold',
                    'rented',
                    'withdrawn',
                    'expired'
                ))
            ");

            return;
        }

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
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                ALTER TABLE properties
                DROP CONSTRAINT IF EXISTS properties_status_check
            ");

            DB::statement("
                ALTER TABLE properties
                ADD CONSTRAINT properties_status_check
                CHECK (status IN (
                    'draft',
                    'pending',
                    'active',
                    'sold',
                    'rented',
                    'withdrawn',
                    'expired'
                ))
            ");

            return;
        }

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
