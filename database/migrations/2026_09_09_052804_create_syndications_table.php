<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syndications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('source_agency_id')
                ->constrained('agencies')
                ->cascadeOnDelete();

            $table->foreignId('target_agency_id')
                ->constrained('agencies')
                ->cascadeOnDelete();

            $table->enum('status', [
                'pending',
                'approved',
                'active',
                'paused',
                'revoked'
            ])->default('pending');

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            $table->unique([
                'property_id',
                'target_agency_id'
            ]);

            $table->index([
                'source_agency_id',
                'status'
            ]);

            $table->index([
                'target_agency_id',
                'status'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syndications');
    }
};