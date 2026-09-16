<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agency_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('client_id')->unique();

            $table->string('secret_hash');

            $table->enum('status', [
                'active',
                'suspended',
                'revoked'
            ])->default('active');

            $table->timestamp('last_used_at')->nullable();

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index([
                'agency_id',
                'status'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};