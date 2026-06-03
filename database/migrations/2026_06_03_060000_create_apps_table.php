<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('tenant_id');
            $table->string('name');
            $table->enum('platform', ['ios', 'android', 'both'])->default('both');

            // iOS fields
            $table->string('ios_bundle_id')->nullable();
            $table->string('ios_team_id', 20)->nullable();
            $table->string('ios_app_id', 300)->nullable()->comment('Computed: TEAMID.BUNDLEID');
            $table->string('ios_store_url', 500)->nullable();
            $table->string('ios_min_version', 20)->nullable();

            // Android fields
            $table->string('android_package_name')->nullable();
            $table->json('android_sha256_fingerprints')->nullable();
            $table->string('android_store_url', 500)->nullable();

            // Shared
            $table->string('uri_scheme', 60)->nullable()->comment('Without ://');
            $table->string('web_fallback_url', 500)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
};
