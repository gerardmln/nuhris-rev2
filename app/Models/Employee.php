<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Employee $employee): void {
            if (filled($employee->employee_id)) {
                return;
            }

            $employee->employee_id = static::generateEmployeeId();
        });
    }

    public static function generateEmployeeId(?int $year = null): string
    {
        $year ??= now()->year;
        $yearToken = (string) $year;

        $latestSequence = static::withTrashed()
            ->pluck('employee_id')
            ->map(function (string $employeeId) use ($yearToken): ?int {
                if (! preg_match('/(?:^|\\D)'.preg_quote($yearToken, '/').'-?(\\d+)(?:\\D|$)/', $employeeId, $matches)) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter()
            ->max() ?? 0;

        return sprintf('%s-%03d', $year, $latestSequence + 1);
    }

    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'department_id',
        'position',
        'employment_type',
        'ranking',
        'status',
        'hire_date',
        'resume_last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'resume_last_updated_at' => 'date',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(EmployeeCredential::class);
    }

    public function latestResumeCredential(): HasOne
    {
        return $this->hasOne(EmployeeCredential::class)
            ->where('credential_type', 'resume')
            ->where('status', 'verified')
            ->latestOfMany('updated_at');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function wfhMonitoringSubmissions(): HasMany
    {
        return $this->hasMany(WfhMonitoringSubmission::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function scheduleSubmissions(): HasMany
    {
        return $this->hasMany(EmployeeScheduleSubmission::class);
    }

    public function approvedScheduleSubmission(): HasMany
    {
        return $this->hasMany(EmployeeScheduleSubmission::class)->where('status', EmployeeScheduleSubmission::STATUS_APPROVED);
    }

    public function resumeExpiresAt(): ?Carbon
    {
        return $this->latestResumeCredential?->expires_at;
    }

    public function isResumeExpiringSoon(?Carbon $referenceDate = null): bool
    {
        $expiresAt = $this->resumeExpiresAt();

        if (! $expiresAt) {
            return false;
        }

        $referenceDate ??= now();

        return $expiresAt->betweenIncluded(
            $referenceDate->copy()->startOfDay(),
            $referenceDate->copy()->addDays(30)->endOfDay()
        );
    }

    public function isResumeExpired(?Carbon $referenceDate = null): bool
    {
        $expiresAt = $this->resumeExpiresAt();

        if (! $expiresAt) {
            return false;
        }

        $referenceDate ??= now();

        return $expiresAt->lt($referenceDate->copy()->startOfDay());
    }

    public function latestResumeUpdatedAt(): ?Carbon
    {
        return $this->latestResumeCredential?->updated_at?->copy();
    }
}
