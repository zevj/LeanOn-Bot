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
        // 1. users table
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'department'], 'users_role_dept_idx');
            $table->index(['role', 'gender'], 'users_role_gender_idx');
            $table->index(['role', 'age'], 'users_role_age_idx');
        });

        // 2. crisis_alerts table
        Schema::table('crisis_alerts', function (Blueprint $table) {
            $table->index(['user_id', 'admin_email_notified', 'admin_email_sent_at'], 'crisis_user_notified_sent_idx');
        });

        // 3. conversations table
        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['user_id', 'is_archived', 'updated_at'], 'conv_user_archived_updated_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_dept_idx');
            $table->dropIndex('users_role_gender_idx');
            $table->dropIndex('users_role_age_idx');
        });

        Schema::table('crisis_alerts', function (Blueprint $table) {
            $table->dropIndex('crisis_user_notified_sent_idx');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conv_user_archived_updated_idx');
        });
    }
};
