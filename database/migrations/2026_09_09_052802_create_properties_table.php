<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agency_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('reference')->nullable()->index();
            $table->string('title');
            $table->string('slug')->unique();

            $table->text('description')->nullable();

            $table->enum('listing_type', [
                'sale',
                'rent'
            ]);

            $table->string('property_type')->index();

            $table->decimal('price', 15, 2)->nullable();
            $table->string('currency', 3)->default('THB');

            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();

            $table->decimal('land_size', 12, 2)->nullable();
            $table->decimal('building_size', 12, 2)->nullable();

            $table->string('size_unit')->default('sqm');

            $table->string('address')->nullable();
            $table->string('subdistrict')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable()->index();
            $table->string('postcode')->nullable();
            $table->string('country')->default('Thailand');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->enum('status', [
                'draft',
                'pending',
                'active',
                'sold',
                'rented',
                'withdrawn',
                'expired'
            ])->default('draft');

            $table->boolean('featured')->default(false);

            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['listing_type', 'status']);
            $table->index(['province', 'district']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};