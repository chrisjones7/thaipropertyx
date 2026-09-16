<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Remove the original global-only unique constraint.
         */
        Schema::table('taxonomy_mappings', function (Blueprint $table) {
            $table->dropUnique([
                'source_type',
                'source_taxonomy',
                'source_slug',
            ]);
        });

        /*
         * Add optional agency scope.
         *
         * NULL = global/default mapping.
         * agency_id = mapping specifically for that agency.
         */
        Schema::table('taxonomy_mappings', function (Blueprint $table) {
            $table->foreignId('agency_id')
                ->nullable()
                ->after('id')
                ->constrained('agencies')
                ->nullOnDelete();

            $table->index([
                'agency_id',
                'source_type',
                'source_taxonomy',
                'source_slug',
            ], 'taxonomy_mappings_agency_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('taxonomy_mappings', function (Blueprint $table) {
            $table->dropIndex('taxonomy_mappings_agency_lookup');
            $table->dropConstrainedForeignId('agency_id');
        });

        Schema::table('taxonomy_mappings', function (Blueprint $table) {
            $table->unique([
                'source_type',
                'source_taxonomy',
                'source_slug',
            ]);
        });
    }
};