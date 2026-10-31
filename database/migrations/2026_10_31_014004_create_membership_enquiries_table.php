<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_enquiries', function (Blueprint $table) {
            $table->id();

            $table->string('company_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone', 50);
            $table->string('business_type', 50);

            $table->string('website_url')->nullable();
            $table->string('website_platform', 50);
            $table->string('wordpress_theme')->nullable();

            $table->string('location')->nullable();
            $table->text('message')->nullable();

            $table->string('status', 30)->default('new');
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index('email');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_enquiries');
    }
};