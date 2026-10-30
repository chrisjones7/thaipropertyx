<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('syndications', function (Blueprint $table) {
            $table->string('commission_model')->nullable();
            $table->decimal('listing_agency_split', 5, 2)->nullable();
            $table->decimal('cooperating_agency_split', 5, 2)->nullable();
            $table->decimal('sale_price_commission_percent', 5, 2)->nullable();
            $table->decimal('fixed_commission_amount', 15, 2)->nullable();
            $table->string('commission_currency', 3)->nullable();
            $table->string('vat_treatment')->nullable();
            $table->string('commission_payable_when')->nullable();
            $table->text('sharing_terms')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('syndications', function (Blueprint $table) {
            $table->dropColumn([
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