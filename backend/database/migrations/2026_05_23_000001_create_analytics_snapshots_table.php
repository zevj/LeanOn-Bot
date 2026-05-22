<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics Snapshots Table
 *
 * Stores precomputed daily analytics containing ONLY anonymized,
 * aggregated statistics. No user IDs, names, emails, or message
 * content is ever stored in this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->unique();
            $table->unsignedInteger('daily_active_users')->default(0);
            $table->unsignedInteger('total_conversations')->default(0);
            $table->unsignedInteger('new_conversations')->default(0);
            $table->unsignedInteger('total_messages')->default(0);
            $table->decimal('avg_session_minutes', 8, 2)->default(0);
            $table->json('peak_usage_hours')->nullable();
            $table->json('emotion_distribution')->nullable();
            $table->json('topic_frequency')->nullable();
            $table->unsignedInteger('crisis_alert_count')->default(0);
            $table->unsignedInteger('fallback_count')->default(0);
            $table->json('sentiment_scores')->nullable();
            $table->json('trend_metrics')->nullable();
            $table->timestamps();

            $table->index('snapshot_date', 'analytics_snapshot_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_snapshots');
    }
};
