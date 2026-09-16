<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('agency_id')
                ->nullable()
                ->after('id')
                ->constrained('agencies')
                ->nullOnDelete();

            $table->enum('role', [
                'platform_admin',
                'agency_admin',
                'agent'
            ])->default('agent')->after('agency_id');

            $table->enum('status', [
                'pending',
                'active',
                'suspended',
                'disabled'
            ])->default('pending')->after('role');

            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['agency_id', 'status']);
            $table->dropForeign(['agency_id']);
            $table->dropColumn([
                'agency_id',
                'role',
                'status'
            ]);
        });
    }
};