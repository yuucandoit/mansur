<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardList extends Model
{
    protected $fillable = ['title', 'project_id', 'user_id', 'order', 'is_done', 'is_archive'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** All tasks in this list */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'list_id');
    }

    /** Active tasks ordered */
    public function activeTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'list_id')
                    ->where('is_archive', 0)
                    ->orderBy('order');
    }
}
