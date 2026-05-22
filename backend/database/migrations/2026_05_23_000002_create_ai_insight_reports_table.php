<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI Insight Reports Table
 *
 * Stores AI-generated natural-language insights, recommendations,
 * and wellness summaries. Generated from anonymized statistics only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_insight_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analytics_snapshot_id')->nullable()->constrained('analytics_snapshots')->nullOnDelete();
            $table->enum('report_type', ['daily', 'weekly', 'monthly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->json('analytics_snapshot')->nullable();
            $table->json('insights')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('trends')->nullable();
            $table->text('wellness_summary')->nullable();
            $table->json('anomalies')->nullable();
            $table->string('ai_provider', 50)->default('gemini');
            $table->string('status', 30)->default('completed');
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('request_count')->default(1);
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('cache_expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['report_type', 'period_end'], 'ai_report_type_period_idx');
            $table->unique(['report_type', 'period_end'], 'ai_report_type_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_insight_reports');
    }
};
