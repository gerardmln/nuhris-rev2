<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_calendar_entries', function (Blueprint $table) {
            $table->enum('day_type', ['working', 'non_working'])->default('non_working')->after('title');
        });

        DB::table('academic_calendar_entries')
            ->where('entry_type', 'event')
            ->update(['day_type' => 'working']);

        DB::table('academic_calendar_entries')
            ->where('entry_type', 'holiday')
            ->update(['day_type' => 'non_working']);
    }

    public function down(): void
    {
        Schema::table('academic_calendar_entries', function (Blueprint $table) {
            $table->dropColumn('day_type');
        });
    }
};