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
        'target_user_type',
        'target_office',
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
            'target_user_type' => 'integer',
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

    public function getAudienceLabelAttribute(): string
    {
        if ($this->target_office) {
            return $this->target_office;
        }

        return match ($this->target_user_type) {
            User::TYPE_ADMIN => 'Admin',
            User::TYPE_HR => 'HR',
            User::TYPE_EMPLOYEE => 'Employee',
            default => 'Everyone',
        };
    }
}
