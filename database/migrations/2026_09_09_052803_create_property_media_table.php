<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('media_type', [
                'image',
                'floorplan',
                'video',
                'document'
            ])->default('image');

            $table->string('url');
            $table->string('thumbnail_url')->nullable();

            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->index(['property_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_media');
    }
};