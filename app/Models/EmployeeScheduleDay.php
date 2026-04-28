<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeScheduleDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_submission_id',
        'day_name',
        'day_index',
        'has_work',
        'time_in',
        'time_out',
    ];

    protected function casts(): array
    {
        return [
            'day_index' => 'integer',
            'has_work' => 'boolean',
            'time_in' => 'datetime:H:i',
            'time_out' => 'datetime:H:i',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(EmployeeScheduleSubmission::class, 'schedule_submission_id');
    }
}