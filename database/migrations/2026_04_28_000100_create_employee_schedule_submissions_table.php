<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_schedule_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('semester_label');
            $table->string('academic_year')->nullable();
            $table->enum('status', ['pending', 'approved', 'declined', 'reset'])->default('pending');
            $table->dateTime('submitted_at');
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['employee_id', 'is_current']);
        });

        Schema::create('employee_schedule_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_submission_id')->constrained('employee_schedule_submissions')->cascadeOnDelete();
            $table->string('day_name', 16);
            $table->unsignedTinyInteger('day_index');
            $table->boolean('has_work')->default(true);
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->timestamps();

            $table->unique(['schedule_submission_id', 'day_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedule_days');
        Schema::dropIfExists('employee_schedule_submissions');
    }
};