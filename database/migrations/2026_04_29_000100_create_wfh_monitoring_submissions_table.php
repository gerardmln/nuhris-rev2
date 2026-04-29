<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wfh_monitoring_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('wfh_date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->dateTime('submitted_at');
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['employee_id', 'wfh_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wfh_monitoring_submissions');
    }
};
