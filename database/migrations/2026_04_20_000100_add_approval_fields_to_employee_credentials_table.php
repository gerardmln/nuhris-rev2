<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_credentials', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_credentials', 'original_filename')) {
                $table->string('original_filename')->nullable()->after('file_path');
            }
            if (! Schema::hasColumn('employee_credentials', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('employee_credentials', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (! Schema::hasColumn('employee_credentials', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_credentials', function (Blueprint $table) {
            if (Schema::hasColumn('employee_credentials', 'review_notes')) {
                $table->dropColumn('review_notes');
            }
            if (Schema::hasColumn('employee_credentials', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
            if (Schema::hasColumn('employee_credentials', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }
            if (Schema::hasColumn('employee_credentials', 'original_filename')) {
                $table->dropColumn('original_filename');
            }
        });
    }
};
