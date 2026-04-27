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
        Schema::table('announcements', function (Blueprint $table): void {
            $table->string('target_employee_type')->nullable()->after('target_user_type');
            $table->foreignId('target_department_id')->nullable()->after('target_employee_type')->constrained('departments')->nullOnDelete();
            $table->string('target_ranking')->nullable()->after('target_department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('target_department_id');
            $table->dropColumn(['target_employee_type', 'target_ranking']);
        });
    }
};