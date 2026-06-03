<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->string('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->string('google_id', 100)->nullable()->after('two_factor_confirmed_at');
            $table->string('avatar_url', 500)->nullable()->after('google_id');
            $table->timestamp('last_login_at')->nullable()->after('avatar_url');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'uuid', 'two_factor_secret', 'two_factor_recovery_codes',
                'two_factor_confirmed_at', 'google_id', 'avatar_url',
                'last_login_at', 'last_login_ip', 'deleted_at',
            ]);
        });
    }
};
