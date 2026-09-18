<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->decimal('listing_agency_split', 5, 2)
                ->nullable()
                ->default(50.00)
                ->change();

            $table->decimal('cooperating_agency_split', 5, 2)
                ->nullable()
                ->default(50.00)
                ->change();

            $table->decimal('sale_price_commission_percent', 5, 2)
                ->nullable()
                ->change();

            $table->decimal('fixed_commission_amount', 15, 2)
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->decimal('listing_agency_split', 5, 2)
                ->nullable(false)
                ->default(50.00)
                ->change();

            $table->decimal('cooperating_agency_split', 5, 2)
                ->nullable(false)
                ->default(50.00)
                ->change();
        });
    }
};
