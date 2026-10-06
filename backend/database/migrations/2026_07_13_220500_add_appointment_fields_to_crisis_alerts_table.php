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
        Schema::table('crisis_alerts', function (Blueprint $table) {
            $table->date('appointment_date')->nullable()->after('admin_email_notified');
            $table->string('appointment_time')->nullable()->after('appointment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crisis_alerts', function (Blueprint $table) {
            $table->dropColumn('appointment_date');
            $table->dropColumn('appointment_time');
        });
    }
};
