<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->boolean('exchange_available')
                ->default(false)
                ->after('featured');

            $table->timestamp('exchange_available_at')
                ->nullable()
                ->after('exchange_available');

            $table->timestamp('exchange_withdrawn_at')
                ->nullable()
                ->after('exchange_available_at');

            $table->index('exchange_available');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['exchange_available']);

            $table->dropColumn([
                'exchange_available',
                'exchange_available_at',
                'exchange_withdrawn_at',
            ]);
        });
    }
};
