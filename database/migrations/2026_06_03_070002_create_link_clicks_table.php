<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained('links')->cascadeOnDelete();
            $table->timestamp('clicked_at')->useCurrent();

            // Device
            $table->enum('platform', ['ios', 'android', 'desktop', 'bot', 'unknown'])->default('unknown');
            $table->string('os_version', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->enum('device_type', ['mobile', 'tablet', 'desktop', 'bot'])->default('desktop');

            // Outcome
            $table->enum('outcome', [
                'app_opened', 'store_redirect_ios', 'store_redirect_android',
                'web_fallback', 'link_expired', 'link_inactive',
                'password_required', 'max_clicks_reached', 'bot_filtered',
            ])->default('app_opened');

            // Location (GeoIP — populated later, null for now)
            $table->char('country_code', 2)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();

            // Source
            $table->string('ip_hash', 64)->nullable();
            $table->string('referrer_domain', 255)->nullable();
            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();

            $table->boolean('is_unique')->default(false);

            $table->index(['link_id', 'clicked_at']);
            $table->index(['link_id', 'platform']);
            $table->index(['link_id', 'outcome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_clicks');
    }
};
