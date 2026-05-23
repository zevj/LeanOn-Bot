<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add department, gender, and flag_reason to crisis_alerts.
 *
 * - department / gender: copied from the user at alert creation time so
 *   analytics queries never need to join back to the users table.
 * - flag_reason: a short human-readable label for why the message was flagged
 *   (e.g. "hopelessness", "self-harm mention", "suicidal ideation").
 * - is_classified: false until an admin manually assigns a severity level.
 *   Replaces the old auto-severity logic in ChatController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crisis_alerts', function (Blueprint $table) {
            // Denormalized user context for analytics (no PII — just aggregation fields)
            $table->string('department')->nullable()->after('user_id');
            $table->string('gender')->nullable()->after('department');

            // Short reason label produced by local keyword detection
            $table->string('flag_reason')->nullable()->after('detected_keywords');

            // Track whether an admin has manually classified this alert
            $table->boolean('is_classified')->default(false)->after('status');

            // Index for department-level analytics queries
            $table->index('department', 'crisis_dept_idx');
            $table->index('is_classified', 'crisis_classified_idx');
        });
    }

    public function down(): void
    {
        Schema::table('crisis_alerts', function (Blueprint $table) {
            $table->dropIndex('crisis_dept_idx');
            $table->dropIndex('crisis_classified_idx');
            $table->dropColumn(['department', 'gender', 'flag_reason', 'is_classified']);
        });
    }
};
