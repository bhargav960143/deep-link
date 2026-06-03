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
        Schema::table('links', function (Blueprint $table) {
            $table->string('ios_fallback_url', 500)->nullable()->after('web_fallback_url');
            $table->string('android_fallback_url', 500)->nullable()->after('ios_fallback_url');
            $table->boolean('show_interstitial')->default(false)->after('link_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('links', function (Blueprint $table) {
            $table->dropColumn(['ios_fallback_url', 'android_fallback_url', 'show_interstitial']);
        });
    }
};
