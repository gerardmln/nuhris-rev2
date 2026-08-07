<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_calendar_entries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('entry_type', ['holiday', 'event']);
            $table->date('event_date');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['entry_type', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_calendar_entries');
    }
};