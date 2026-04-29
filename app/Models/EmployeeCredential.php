<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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

    public function expiringSoonThresholdDays(): ?int
    {
        return match ($this->credential_type) {
            'resume' => 30,
            'prc' => 90,
            default => null,
        };
    }

    public function effectiveExpiresAt(): ?Carbon
    {
        if ($this->credential_type !== 'resume') {
            return $this->expires_at?->copy();
        }

        if ($this->expires_at && $this->created_at && $this->expires_at->isSameDay($this->created_at)) {
            return $this->created_at->copy()->addYear()->startOfDay();
        }

        if ($this->expires_at) {
            return $this->expires_at->copy();
        }

        return $this->created_at?->copy()->addYear()->startOfDay();
    }

    public function isExpiringSoon(?Carbon $referenceDate = null): bool
    {
        $thresholdDays = $this->expiringSoonThresholdDays();
        $expiresAt = $this->effectiveExpiresAt();

        if (! $thresholdDays || ! $expiresAt) {
            return false;
        }

        $referenceDate ??= now();

        return $expiresAt->betweenIncluded(
            $referenceDate->copy()->startOfDay(),
            $referenceDate->copy()->addDays($thresholdDays)->endOfDay()
        );
    }

    public function isExpired(?Carbon $referenceDate = null): bool
    {
        $expiresAt = $this->effectiveExpiresAt();

        if (! $expiresAt) {
            return false;
        }

        $referenceDate ??= now();

        return $expiresAt->lt($referenceDate->copy()->startOfDay());
    }
}
