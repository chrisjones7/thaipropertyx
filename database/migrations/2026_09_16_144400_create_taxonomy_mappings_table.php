<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxonomy_mappings', function (Blueprint $table) {
            $table->id();

            /*
             * Source platform.
             *
             * Examples:
             * houzez
             * wordpress
             * other
             */
            $table->string('source_type', 50);

            /*
             * Source taxonomy.
             *
             * Examples:
             * property_feature
             * property_label
             * property_type
             * property_status
             */
            $table->string('source_taxonomy', 100);

            /*
             * Exact source-system slug.
             *
             * Example:
             * gym
             * barbeque
             * swimming-pool
             */
            $table->string('source_slug', 150);

            /*
             * Central TPX taxonomy being mapped to.
             *
             * Examples:
             * property_feature
             * property_label
             * property_type
             * property_status
             */
            $table->string('target_taxonomy', 100);

            /*
             * ID of the TPX canonical record.
             */
            $table->unsignedBigInteger('target_id');

            /*
             * Optional description / administrator notes.
             */
            $table->text('notes')->nullable();

            /*
             * Allows a mapping to be temporarily disabled without
             * deleting the mapping.
             */
            $table->boolean('active')->default(true);

            $table->timestamps();

            /*
             * One source term should map to one central TPX term.
             */
            $table->unique([
                'source_type',
                'source_taxonomy',
                'source_slug',
            ]);

            $table->index([
                'target_taxonomy',
                'target_id',
            ]);

            $table->index([
                'active',
                'source_type',
                'source_taxonomy',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxonomy_mappings');
    }
};