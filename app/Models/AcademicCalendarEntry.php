<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AcademicCalendarEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'entry_type',
        'day_type',
        'event_date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('event_date', '>=', today());
    }

    public function getTypeLabelAttribute(): string
    {
        return ucfirst($this->entry_type ?? 'event');
    }

    public function getBadgeClassAttribute(): string
    {
        return $this->entry_type === 'holiday'
            ? 'bg-amber-100 text-amber-800'
            : 'bg-blue-100 text-blue-800';
    }

    public function isNonWorking(): bool
    {
        return $this->day_type === 'non_working';
    }
}