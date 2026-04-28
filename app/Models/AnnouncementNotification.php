<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_id',
        'user_id',
        'is_read',
        'read_at',
        'redirect_url',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function scopeVisible($query)
    {
        return $query->whereHas('announcement', function ($q) {
            $q->visible();
        });
    }

    public function getTitleTextAttribute(): string
    {
        return $this->announcement?->title ?? 'Notification';
    }

    public function getContentTextAttribute(): string
    {
        return $this->announcement?->content ?? 'No details available.';
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
