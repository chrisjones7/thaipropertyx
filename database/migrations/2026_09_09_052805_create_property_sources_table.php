<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_sources', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('agency_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('source_type', [
                'manual',
                'api',
                'xml',
                'csv',
                'wordpress',
                'houzez'
            ])->default('manual');

            $table->string('external_id')->nullable();
            $table->string('external_url')->nullable();

            $table->string('feed_name')->nullable();

            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique([
                'agency_id',
                'source_type',
                'external_id'
            ]);

            $table->index([
                'property_id',
                'source_type'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_sources');
    }
};