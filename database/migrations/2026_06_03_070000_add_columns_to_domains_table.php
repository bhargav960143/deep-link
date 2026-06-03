<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->enum('type', ['subdomain', 'custom'])->default('subdomain')->after('domain');
            $table->boolean('is_primary')->default(false)->after('type');
            $table->enum('status', ['pending', 'active', 'failed', 'suspended'])->default('active')->after('is_primary');
            $table->string('verification_token', 64)->nullable()->after('status');
            $table->timestamp('verified_at')->nullable()->after('verification_token');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['type', 'is_primary', 'status', 'verification_token', 'verified_at']);
        });
    }
};
