<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('legal_name')->nullable();
            $table->string('registration_number')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            $table->string('logo')->nullable();

            $table->text('description')->nullable();

            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->default('Thailand');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->enum('status', [
                'pending',
                'active',
                'suspended',
                'disabled'
            ])->default('pending');

            $table->boolean('verified')->default(false);

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};