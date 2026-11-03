<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_duplicate_matches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained('properties')
                ->cascadeOnDelete();

            $table->foreignId('matching_property_id')
                ->constrained('properties')
                ->cascadeOnDelete();

            $table->unsignedInteger('matching_images')->default(0);

            $table->string('classification', 30);

            $table->string('review_status', 30)
                ->default('pending');

            $table->timestamp('last_checked_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['property_id', 'matching_property_id'],
                'property_duplicate_pair_unique'
            );

            $table->index(['property_id', 'review_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_duplicate_matches');
    }
};
