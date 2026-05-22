<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cached Analytics Summaries Table
 *
 * Durable cache mirror for free-tier deployments where in-memory cache may
 * disappear after Render sleeps. Stores anonymized API payloads only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cached_analytics_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique();
            $table->string('summary_type', 50)->index();
            $table->json('payload');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cached_analytics_summaries');
    }
};
