<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('razorpay_plan_id_monthly', 100)->nullable();
            $table->string('razorpay_plan_id_yearly', 100)->nullable();
            $table->unsignedInteger('price_monthly')->default(0);
            $table->unsignedInteger('price_yearly')->default(0);
            $table->integer('links_limit')->default(100);
            $table->integer('clicks_limit')->default(10000);
            $table->integer('apps_limit')->default(1);
            $table->integer('team_members_limit')->default(1);
            $table->integer('custom_domains_limit')->default(0);
            $table->boolean('api_access')->default(false);
            $table->boolean('webhooks')->default(false);
            $table->integer('analytics_retention_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
