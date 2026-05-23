<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crisis_alerts', function (Blueprint $table) {
            $table->timestamp('admin_email_sent_at')->nullable()->after('status');
            $table->boolean('admin_email_notified')->default(false)->after('admin_email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('crisis_alerts', function (Blueprint $table) {
            $table->dropColumn('admin_email_sent_at');
            $table->dropColumn('admin_email_notified');
        });
    }
};
