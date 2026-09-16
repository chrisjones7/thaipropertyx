<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Property Features
        |--------------------------------------------------------------------------
        |
        | Master list of amenities/features available throughout TPX.
        |
        | Examples:
        | - Private Pool
        | - Communal Pool
        | - 24-Hour Security
        | - Clubhouse
        | - Fitness / Gym
        |
        | The slug gives us a stable identifier for API/Houzez mapping even
        | if the displayed name is changed later.
        |
        */

        Schema::create('property_features', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);

            $table->string('slug', 150)
                ->unique();

            $table->string('category', 100)
                ->nullable();

            /*
             * Optional Houzez mapping information.
             *
             * Different participating Houzez websites may use different
             * term IDs, so site-specific mappings will ultimately belong
             * in the connector layer. This field is simply a canonical
             * Houzez-compatible slug where appropriate.
             */
            $table->string('houzez_slug', 150)
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->boolean('active')
                ->default(true);

            $table->timestamps();

            $table->index([
                'category',
                'active',
            ]);

            $table->index('sort_order');
        });


        /*
        |--------------------------------------------------------------------------
        | Property ↔ Feature Relationship
        |--------------------------------------------------------------------------
        |
        | A property can have many features.
        | A feature can belong to many properties.
        |
        */

        Schema::create('property_property_feature', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained('properties')
                ->cascadeOnDelete();

            $table->foreignId('property_feature_id')
                ->constrained('property_features')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'property_id',
                'property_feature_id',
            ]);

            $table->index('property_feature_id');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_property_feature');
        Schema::dropIfExists('property_features');
    }
};