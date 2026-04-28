<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_schedule_submissions', function (Blueprint $table) {
            $table->string('term_label')->nullable()->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('employee_schedule_submissions', function (Blueprint $table) {
            $table->dropColumn('term_label');
        });
    }
};