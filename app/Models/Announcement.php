<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'priority',
        'target_employee_type',
        'target_office',
        'target_department_id',
        'target_ranking',
        'published_at',
        'expires_at',
        'is_published',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at' => 'date',
            'is_published' => 'boolean',
            'target_department_id' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AnnouncementNotification::class);
    }

    public function targetDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    public function getAudienceLabelAttribute(): string
    {
        $segments = [];

        if ($this->target_employee_type) {
            $segments[] = $this->target_employee_type === 'faculty' ? 'Faculty' : 'Admin Support Personnel';
        }

        if ($this->target_office) {
            $segments[] = $this->target_office;
        }

        if ($this->targetDepartment?->name) {
            $segments[] = $this->targetDepartment->name;
        }

        if ($this->target_ranking) {
            $segments[] = $this->target_ranking;
        }

        return $segments ? implode(' · ', array_values(array_unique($segments))) : 'Everyone';
    }
}
