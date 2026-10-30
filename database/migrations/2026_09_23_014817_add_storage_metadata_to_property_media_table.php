<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_media', function (Blueprint $table) {
            $table->string('storage_disk', 50)->nullable()->after('media_type');
            $table->string('storage_path')->nullable()->after('storage_disk');

            $table->unsignedBigInteger('file_size')->nullable()->after('thumbnail_url');
            $table->string('mime_type', 100)->nullable()->after('file_size');

            $table->unsignedInteger('width')->nullable()->after('mime_type');
            $table->unsignedInteger('height')->nullable()->after('width');

            $table->index('storage_disk');
        });
    }

    public function down(): void
    {
        Schema::table('property_media', function (Blueprint $table) {
            $table->dropIndex(['storage_disk']);

            $table->dropColumn([
                'storage_disk',
                'storage_path',
                'file_size',
                'mime_type',
                'width',
                'height',
            ]);
        });
    }
};