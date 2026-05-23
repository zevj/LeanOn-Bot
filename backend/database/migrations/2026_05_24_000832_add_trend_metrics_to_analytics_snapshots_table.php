<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_snapshots', function (Blueprint $table) {
            if (!Schema::hasColumn('analytics_snapshots', 'trend_metrics')) {
                $table->json('trend_metrics')->nullable()->after('sentiment_scores');
            }
        });
    }

    public function down(): void
    {
        Schema::table('analytics_snapshots', function (Blueprint $table) {
            $table->dropColumn('trend_metrics');
        });
    }
};
