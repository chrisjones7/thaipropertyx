<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {

            $table->boolean('sharing_enabled')
                ->default(true)
                ->after('verified_at');

            // split | sale_percentage | fixed_fee | custom
            $table->string('commission_model', 30)
                ->default('split')
                ->after('sharing_enabled');

            // Used when commission_model = split
            $table->decimal('listing_agency_split', 5, 2)
                ->default(50.00)
                ->after('commission_model');

            $table->decimal('cooperating_agency_split', 5, 2)
                ->default(50.00)
                ->after('listing_agency_split');

            // Used when commission_model = sale_percentage
            $table->decimal('sale_price_commission_percent', 5, 2)
                ->nullable()
                ->after('cooperating_agency_split');

            // Used when commission_model = fixed_fee
            $table->decimal('fixed_commission_amount', 15, 2)
                ->nullable()
                ->after('sale_price_commission_percent');

            $table->string('commission_currency', 3)
                ->default('THB')
                ->after('fixed_commission_amount');

            // How VAT is treated in the displayed agreement
            $table->string('vat_treatment', 20)
                ->default('excluded')
                ->after('commission_currency');

            // completion | transfer | custom
            $table->string('commission_payable_when', 30)
                ->default('transfer')
                ->after('vat_treatment');

            $table->text('sharing_terms')
                ->nullable()
                ->after('commission_payable_when');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn([
                'sharing_enabled',
                'commission_model',
                'listing_agency_split',
                'cooperating_agency_split',
                'sale_price_commission_percent',
                'fixed_commission_amount',
                'commission_currency',
                'vat_treatment',
                'commission_payable_when',
                'sharing_terms',
            ]);
        });
    }
};
