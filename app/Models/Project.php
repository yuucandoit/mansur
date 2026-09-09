<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'user_id', 'workspace_id',
        'background_id', 'start_date', 'end_date', 'is_starred', 'color'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    // Column colors for board columns
    public static array $columnColors = [
        '#ef4444', '#f59e0b', '#8b5cf6', '#10b981',
        '#3b82f6', '#ec4899', '#6366f1', '#14b8a6',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** All board lists (active) */
    public function boardLists(): HasMany
    {
        return $this->hasMany(BoardList::class);
    }

    /** Get active lists ordered */
    public function activeLists(): HasMany
    {
        return $this->hasMany(BoardList::class)
                    ->where('is_archive', 0)
                    ->orderBy('order');
    }

    /** All tasks */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function getSlugOrIdAttribute(): string
    {
        return $this->slug ?? (string) $this->id;
    }
}
