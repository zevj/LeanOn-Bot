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
        Schema::table('session_logs', function (Blueprint $table) {
            $table->dateTime('session_start')->change();
            $table->dateTime('session_end')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('session_logs', function (Blueprint $table) {
            $table->timestamp('session_start')->change();
            $table->timestamp('session_end')->nullable()->change();
        });
    }
};
