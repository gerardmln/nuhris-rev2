<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'record_date',
        'time_in',
        'time_out',
        'scheduled_time_in',
        'scheduled_time_out',
        'tardiness_minutes',
        'undertime_minutes',
        'overtime_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'time_in' => 'datetime:H:i',
            'time_out' => 'datetime:H:i',
            'scheduled_time_in' => 'datetime:H:i',
            'scheduled_time_out' => 'datetime:H:i',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
