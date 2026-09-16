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
        | Property Labels
        |--------------------------------------------------------------------------
        |
        | Master list of marketing/property labels available throughout TPX.
        |
        | Examples:
        | - Reduced Price
        | - Hot Offer
        | - New Listing
        | - Exclusive
        | - Close to Beach
        | - Close to Golf Course
        |
        | These are kept separate from property features because labels
        | describe how a property is marketed or highlighted, whereas
        | features describe physical attributes and amenities.
        |
        */

        Schema::create('property_labels', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);

            $table->string('slug', 150)
                ->unique();

            /*
             * Canonical Houzez-compatible slug where appropriate.
             *
             * Individual Houzez websites may use different taxonomy term
             * IDs, so actual site-specific IDs will be handled later by
             * the connector/mapping layer.
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
                'active',
                'sort_order',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | Property ↔ Label Relationship
        |--------------------------------------------------------------------------
        |
        | We deliberately allow multiple labels per TPX property.
        |
        | Example:
        | - Reduced Price
        | - Close to Beach
        | - Hot Offer
        |
        | Even if a particular Houzez configuration only displays one label,
        | TPX retains the richer central data and the connector can determine
        | how that site's data should be exported.
        |
        */

        Schema::create('property_property_label', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained('properties')
                ->cascadeOnDelete();

            $table->foreignId('property_label_id')
                ->constrained('property_labels')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'property_id',
                'property_label_id',
            ]);

            $table->index('property_label_id');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_property_label');
        Schema::dropIfExists('property_labels');
    }
};
