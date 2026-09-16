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
        Schema::table('properties', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Development / Project
            |--------------------------------------------------------------------------
            */

            $table->string('development', 255)
                ->nullable()
                ->after('description');


            /*
            |--------------------------------------------------------------------------
            | Sale & Rental Availability
            |--------------------------------------------------------------------------
            |
            | A TPX property can be:
            |
            | - For Sale
            | - For Rent
            | - Both
            |
            | We therefore do not rely on the original listing_type field
            | for the long-term TPX data model.
            |
            */

            $table->boolean('for_sale')
                ->default(false)
                ->after('listing_type');

            $table->boolean('for_rent')
                ->default(false)
                ->after('for_sale');

            $table->decimal('sale_price', 15, 2)
                ->nullable()
                ->after('for_rent');

            $table->decimal('rental_price', 15, 2)
                ->nullable()
                ->after('sale_price');

            $table->string('rental_period', 50)
                ->nullable()
                ->after('rental_price');


            /*
            |--------------------------------------------------------------------------
            | Property Details
            |--------------------------------------------------------------------------
            */

            $table->string('furnishing', 50)
                ->nullable()
                ->after('bathrooms');

            $table->unsignedSmallInteger('year_built')
                ->nullable()
                ->after('furnishing');

            /*
             * Houzez-compatible parking/car-port value.
             *
             * Examples:
             * 0 = none
             * 1 = one vehicle
             * 2 = two vehicles
             */
            $table->unsignedSmallInteger('parking_spaces')
                ->nullable()
                ->after('year_built');


            /*
            |--------------------------------------------------------------------------
            | Tenure / Ownership
            |--------------------------------------------------------------------------
            |
            | This is a normalized TPX field. The Houzez connector can map
            | this to the appropriate Houzez/custom field on each website.
            |
            */

            $table->string('tenure', 50)
                ->nullable()
                ->after('parking_spaces');

            $table->unsignedSmallInteger('lease_term_years')
                ->nullable()
                ->after('tenure');


            /*
            |--------------------------------------------------------------------------
            | Condominium / Ownership Information
            |--------------------------------------------------------------------------
            */

            $table->boolean('foreign_quota')
                ->nullable()
                ->after('lease_term_years');


            /*
            |--------------------------------------------------------------------------
            | Common / Maintenance Fees
            |--------------------------------------------------------------------------
            */

            $table->decimal('common_fee', 12, 2)
                ->nullable()
                ->after('foreign_quota');

            $table->string('common_fee_period', 50)
                ->nullable()
                ->after('common_fee');


            /*
            |--------------------------------------------------------------------------
            | Helpful indexes
            |--------------------------------------------------------------------------
            */

            $table->index('development');
            $table->index(['for_sale', 'status']);
            $table->index(['for_rent', 'status']);
        });


        /*
        |--------------------------------------------------------------------------
        | Migrate Existing Property Data
        |--------------------------------------------------------------------------
        |
        | Existing TPX test records currently use:
        |
        | listing_type = sale/rent
        | price        = amount
        |
        | Copy that information into the new normalized fields so we do not
        | lose the existing test property.
        |
        */

        \DB::table('properties')
            ->where('listing_type', 'sale')
            ->update([
                'for_sale' => true,
                'sale_price' => \DB::raw('price'),
            ]);

        \DB::table('properties')
            ->where('listing_type', 'rent')
            ->update([
                'for_rent' => true,
                'rental_price' => \DB::raw('price'),
            ]);
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {

            $table->dropIndex([
                'development',
            ]);

            $table->dropIndex([
                'for_sale',
                'status',
            ]);

            $table->dropIndex([
                'for_rent',
                'status',
            ]);

            $table->dropColumn([
                'development',

                'for_sale',
                'for_rent',

                'sale_price',
                'rental_price',
                'rental_period',

                'furnishing',
                'year_built',
                'parking_spaces',

                'tenure',
                'lease_term_years',

                'foreign_quota',

                'common_fee',
                'common_fee_period',
            ]);
        });
    }
};