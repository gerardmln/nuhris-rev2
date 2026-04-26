<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'credential_type',
        'title',
        'department_id',
        'expires_at',
        'description',
        'file_path',
        'original_filename',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function typeLabel(): string
    {
        return match ($this->credential_type) {
            'resume' => 'Resume',
            'prc' => 'PRC License',
            'seminars' => 'Seminar / Training',
            'degrees' => 'Academic Degree',
            'ranking' => 'Ranking File',
            default => ucfirst((string) $this->credential_type),
        };
    }
}
