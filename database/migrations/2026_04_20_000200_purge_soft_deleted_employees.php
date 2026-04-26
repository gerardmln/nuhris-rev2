<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time cleanup: permanently remove employees that were previously
     * soft-deleted (deleted_at IS NOT NULL) and their linked user accounts.
     *
     * From this release onward the HR delete flow uses a hard delete, so
     * the deleted_at column is effectively unused. This migration flushes
     * the leftover soft-deleted rows so stale records (like a previously
     * "deleted" employee whose email still blocks re-creation via the
     * unique constraint) are actually removed from the database.
     */
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        // Collect emails of soft-deleted employees so we can also remove
        // their linked user login rows (employees <-> users are matched
        // by email, not a foreign key).
        $softDeletedEmails = DB::table('employees')
            ->whereNotNull('deleted_at')
            ->pluck('email')
            ->filter()
            ->all();

        // Hard delete soft-deleted employees. Foreign-key cascades configured
        // in the employee_credentials / attendance_records / leave_balances /
        // leave_requests migrations will take care of child rows.
        DB::table('employees')
            ->whereNotNull('deleted_at')
            ->delete();

        if (! empty($softDeletedEmails) && Schema::hasTable('users')) {
            DB::table('users')
                ->whereIn('email', $softDeletedEmails)
                ->delete();
        }
    }

    /**
     * Cleanup is one-way; there is nothing meaningful to restore.
     */
    public function down(): void
    {
        // no-op
    }
};
