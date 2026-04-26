<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a composite-friendly index that speeds up the dashboard count
     * queries (which filter on resume_last_updated_at and always exclude
     * soft-deleted rows via deleted_at IS NULL).
     *
     * Without this index, PostgreSQL has to full-scan the employees table
     * for every compliance-stat count, which on the Supabase pooler adds
     * ~15-25 seconds of latency per request and blows past PHP's 30s
     * max_execution_time.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->index(['resume_last_updated_at', 'deleted_at'], 'employees_resume_updated_deleted_idx');
            $table->index(['status', 'deleted_at'], 'employees_status_deleted_idx');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropIndex('employees_resume_updated_deleted_idx');
            $table->dropIndex('employees_status_deleted_idx');
        });
    }
};
