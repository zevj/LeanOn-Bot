<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Make crisis_alerts.severity nullable and remove the 'high' legacy value.
 *
 * Before: enum('high','severe','moderate','low') NOT NULL default 'moderate'
 * After:  enum('severe','moderate','low') NULLABLE (no default)
 *
 * Rationale:
 * - severity = null means "unclassified" — awaiting admin manual assignment.
 * - 'high' was a legacy auto-assigned value; the new system uses severe/moderate/low only.
 * - Existing 'high' rows are migrated to 'severe' (closest equivalent).
 *
 * SQLite note: SQLite does not support ALTER COLUMN for enums.
 * We use a raw DB statement for MySQL/PostgreSQL and skip for SQLite.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // Migrate legacy 'high' → 'severe' before changing the enum
        DB::table('crisis_alerts')->where('severity', 'high')->update(['severity' => 'severe']);

        if ($driver === 'pgsql') {
            // PostgreSQL: alter the enum type
            DB::statement("ALTER TABLE crisis_alerts ALTER COLUMN severity DROP DEFAULT");
            DB::statement("ALTER TABLE crisis_alerts ALTER COLUMN severity DROP NOT NULL");
            DB::statement("ALTER TABLE crisis_alerts ALTER COLUMN severity TYPE VARCHAR(20)");
            // Add a check constraint instead of enum for flexibility
            DB::statement("ALTER TABLE crisis_alerts ADD CONSTRAINT crisis_severity_check CHECK (severity IN ('severe','moderate','low') OR severity IS NULL)");
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE crisis_alerts MODIFY COLUMN severity ENUM('severe','moderate','low') NULL DEFAULT NULL");
        }
        // SQLite: no ALTER COLUMN support — severity column stays as-is (text column in SQLite)
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE crisis_alerts DROP CONSTRAINT IF EXISTS crisis_severity_check");
            DB::statement("ALTER TABLE crisis_alerts ALTER COLUMN severity TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE crisis_alerts ALTER COLUMN severity SET NOT NULL");
            DB::statement("ALTER TABLE crisis_alerts ALTER COLUMN severity SET DEFAULT 'moderate'");
        } elseif ($driver === 'mysql') {
            DB::table('crisis_alerts')->whereNull('severity')->update(['severity' => 'moderate']);
            DB::statement("ALTER TABLE crisis_alerts MODIFY COLUMN severity ENUM('high','severe','moderate','low') NOT NULL DEFAULT 'moderate'");
        }
    }
};
