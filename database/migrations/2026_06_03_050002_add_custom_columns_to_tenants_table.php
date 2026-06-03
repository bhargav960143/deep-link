<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('plan_slug', 50)->default('free')->after('name');
            $table->timestamp('plan_expires_at')->nullable()->after('plan_slug');
            $table->timestamp('trial_ends_at')->nullable()->after('plan_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['name', 'plan_slug', 'plan_expires_at', 'trial_ends_at']);
        });
    }
};
