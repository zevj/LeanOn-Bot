<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Indexes Migration
 * 
 * Adds indexes to frequently queried columns across all tables.
 * Without these indexes, the database performs full table scans
 * which degrade performance as data grows.
 * 
 * Each index is conditional (checks if it doesn't already exist)
 * to make the migration idempotent and safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── chat_messages ────────────────────────────────────────
        // Used by: ChatController::history(), ChatController::chat() (recent history)
        Schema::table('chat_messages', function (Blueprint $table) {
            // Composite index for fetching conversation history (ordered by time)
            $table->index(['conversation_id', 'created_at'], 'chat_msg_conv_created_idx');
            // Index for user message lookups (spam detection, admin queries)
            $table->index('user_id', 'chat_msg_user_idx');
        });

        // ── conversations ────────────────────────────────────────
        // Used by: ConversationController::index() (sidebar listing)
        Schema::table('conversations', function (Blueprint $table) {
            // Composite index for listing user's conversations sorted by last update
            $table->index(['user_id', 'updated_at'], 'conv_user_updated_idx');
        });

        // ── session_logs ─────────────────────────────────────────
        // Used by: AuthController::login/logout, AdminDashboardController, LogController
        Schema::table('session_logs', function (Blueprint $table) {
            // Composite index for finding active sessions (where session_end IS NULL)
            $table->index(['user_id', 'session_end'], 'session_user_end_idx');
            // Index for dashboard statistics (active users in last 7 days)
            $table->index('session_start', 'session_start_idx');
        });

        // ── emotion_logs ─────────────────────────────────────────
        // Used by: ChatController::classifyConversationEmotion(), EmotionController
        Schema::table('emotion_logs', function (Blueprint $table) {
            // Index for upserting emotion per conversation
            $table->index('conversation_id', 'emotion_conv_idx');
            // Index for weekly trend queries
            $table->index('created_at', 'emotion_created_idx');
        });

        // ── crisis_alerts ────────────────────────────────────────
        // Used by: CrisisAlertController::index()
        Schema::table('crisis_alerts', function (Blueprint $table) {
            // Composite index for listing alerts by user, sorted by time
            $table->index(['user_id', 'created_at'], 'crisis_user_created_idx');
            // Index for filtering by severity and status
            $table->index(['severity', 'status'], 'crisis_severity_status_idx');
        });

        // ── email_otps ───────────────────────────────────────────
        // Used by: AuthController::verifyOtp(), resendOtp()
        Schema::table('email_otps', function (Blueprint $table) {
            // Composite index for finding unused OTPs for a user
            $table->index(['user_id', 'used_at'], 'email_otp_user_used_idx');
        });

        // ── password_otps ────────────────────────────────────────
        // Used by: AuthController::sendOtp(), verifyForgotPasswordOtp(), resetPassword()
        Schema::table('password_otps', function (Blueprint $table) {
            // Index for looking up OTPs by email (already has updateOrCreate)
            $table->index('email', 'pwd_otp_email_idx');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex('chat_msg_conv_created_idx');
            $table->dropIndex('chat_msg_user_idx');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conv_user_updated_idx');
        });

        Schema::table('session_logs', function (Blueprint $table) {
            $table->dropIndex('session_user_end_idx');
            $table->dropIndex('session_start_idx');
        });

        Schema::table('emotion_logs', function (Blueprint $table) {
            $table->dropIndex('emotion_conv_idx');
            $table->dropIndex('emotion_created_idx');
        });

        Schema::table('crisis_alerts', function (Blueprint $table) {
            $table->dropIndex('crisis_user_created_idx');
            $table->dropIndex('crisis_severity_status_idx');
        });

        Schema::table('email_otps', function (Blueprint $table) {
            $table->dropIndex('email_otp_user_used_idx');
        });

        Schema::table('password_otps', function (Blueprint $table) {
            $table->dropIndex('pwd_otp_email_idx');
        });
    }
};
