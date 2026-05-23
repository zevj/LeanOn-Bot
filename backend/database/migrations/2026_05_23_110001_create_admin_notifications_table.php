<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin Notifications Table
 *
 * Stores real-time notifications for the admin panel.
 * Types:
 *   - crisis_flagged : a new student message was flagged and awaits classification
 *   - report_exported: an admin downloaded an analytics PDF report
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);                    // crisis_flagged | report_exported
            $table->string('title', 150);
            $table->text('message');
            $table->text('detail')->nullable();
            $table->string('icon', 60)->default('bx bx-bell');
            $table->string('color', 30)->default('amber'); // green | amber | red | blue
            $table->boolean('is_read')->default(false);
            $table->json('meta')->nullable();              // e.g. alert_id, period, sections
            $table->timestamps();

            $table->index(['is_read', 'created_at'], 'notif_read_created_idx');
            $table->index('type', 'notif_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
