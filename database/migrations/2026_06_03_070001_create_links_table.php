<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('tenant_id');
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->unsignedInteger('domain_id');
            $table->foreign('domain_id')->references('id')->on('domains')->cascadeOnDelete();

            $table->string('short_code', 20);
            $table->string('destination_path', 2000);
            $table->string('web_fallback_url', 500)->nullable();

            // OG meta
            $table->string('og_title', 255)->nullable();
            $table->string('og_description', 500)->nullable();
            $table->string('og_image_url', 500)->nullable();

            // Options
            $table->enum('link_type', ['universal', 'uri_scheme', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->string('password', 255)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_clicks')->nullable();
            $table->unsignedInteger('click_count')->default(0);

            // Metadata
            $table->string('title', 255)->nullable();
            $table->json('tags')->nullable();
            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['short_code', 'domain_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'is_active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
