
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('property_media', function (Blueprint $table) {
            $table->char('image_sha256', 64)->nullable();
            $table->char('image_phash', 16)->nullable();

            $table->index('image_sha256');
            $table->index('image_phash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_media', function (Blueprint $table) {
            $table->dropIndex(['image_sha256']);
            $table->dropIndex(['image_phash']);

            $table->dropColumn([
                'image_sha256',
                'image_phash',
            ]);
        });
    }
};
