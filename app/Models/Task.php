<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'list_id', 'project_id',
        'user_id', 'order', 'due_date', 'is_done', 'is_archive',
        'parent_id', 'priority', 'is_critical', 'cover'
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(BoardList::class, 'list_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->where('is_archive', 0);
    }

    public function assignees(): HasMany
    {
        return $this->hasMany(Assignee::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comments::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Labels::class);
    }

    // Accessor for priority badge color
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'critical' => 'bg-red-100 text-red-700',
            'high'     => 'bg-orange-100 text-orange-700',
            'medium'   => 'bg-yellow-100 text-yellow-700',
            'low'      => 'bg-gray-100 text-gray-600',
            default    => 'bg-gray-100 text-gray-500',
        };
    }
}
