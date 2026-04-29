<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfhMonitoringSubmission extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'employee_id',
        'submitted_by',
        'reviewed_by',
        'wfh_date',
        'time_in',
        'time_out',
        'file_path',
        'original_filename',
        'status',
        'submitted_at',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'wfh_date' => 'date',
            'time_in' => 'datetime:H:i',
            'time_out' => 'datetime:H:i',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
