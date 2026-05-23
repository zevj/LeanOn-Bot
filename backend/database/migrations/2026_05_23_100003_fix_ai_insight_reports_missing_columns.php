<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix ai_insight_reports table — add columns that were missing from the
 * original table creation (table was created before the full migration ran).
 *
 * Missing columns: analytics_snapshot_id, analytics_snapshot, status,
 *                  error_message, request_count, generated_at,
 *                  cache_expires_at, metadata
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_insight_reports', function (Blueprint $table) {
            // Only add each column if it doesn't already exist
            if (!Schema::hasColumn('ai_insight_reports', 'analytics_snapshot_id')) {
                $table->foreignId('analytics_snapshot_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('analytics_snapshots')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('ai_insight_reports', 'analytics_snapshot')) {
                $table->json('analytics_snapshot')->nullable()->after('period_end');
            }

            if (!Schema::hasColumn('ai_insight_reports', 'status')) {
                $table->string('status', 30)->default('completed')->after('ai_provider');
            }

            if (!Schema::hasColumn('ai_insight_reports', 'error_message')) {
                $table->text('error_message')->nullable()->after('status');
            }

            if (!Schema::hasColumn('ai_insight_reports', 'request_count')) {
                $table->unsignedSmallInteger('request_count')->default(1)->after('error_message');
            }

            if (!Schema::hasColumn('ai_insight_reports', 'generated_at')) {
                $table->timestamp('generated_at')->nullable()->after('request_count');
            }

            if (!Schema::hasColumn('ai_insight_reports', 'cache_expires_at')) {
                $table->timestamp('cache_expires_at')->nullable()->after('generated_at');
            }

            if (!Schema::hasColumn('ai_insight_reports', 'metadata')) {
                $table->json('metadata')->nullable()->after('cache_expires_at');
            }
        });

        // Add unique index if it doesn't exist
        try {
            Schema::table('ai_insight_reports', function (Blueprint $table) {
                $table->unique(['report_type', 'period_end'], 'ai_report_type_period_unique');
            });
        } catch (\Exception $e) {
            // Index already exists — safe to ignore
        }
    }

    public function down(): void
    {
        Schema::table('ai_insight_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('analytics_snapshot_id');
            $table->dropColumn([
                'analytics_snapshot',
                'status',
                'error_message',
                'request_count',
                'generated_at',
                'cache_expires_at',
                'metadata',
            ]);
        });
    }
};
