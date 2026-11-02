<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'user_id', 'workspace_id',
        'background_id', 'start_date', 'end_date', 'is_starred', 'color',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function memberRecords(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
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

    /**
     * Projects the given user may access: owned projects, projects they are a
     * member of, and every project in a workspace they own or administer.
     */
    public function scopeVisibleTo($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('projects.user_id', $user->id)
                ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id))
                ->orWhereHas('workspace', fn ($w) => $w->where('user_id', $user->id))
                ->orWhereHas('workspace.members', function ($m) use ($user) {
                    $m->where('users.id', $user->id)
                        ->whereIn('workspace_members.role', ['owner', 'admin']);
                });
        });
    }

    public function hasMember(User $user): bool
    {
        return $this->user_id === $user->id
            || $this->members()->where('users.id', $user->id)->exists();
    }

    public function roleOf(User $user): ?string
    {
        if ($this->user_id === $user->id) {
            return 'manager';
        }

        $member = $this->members()->where('users.id', $user->id)->first();

        return $member?->pivot?->role;
    }

    public function getSlugOrIdAttribute(): string
    {
        return $this->slug ?? (string) $this->id;
    }

    /**
     * Users who can be assigned to tasks in this project: project members,
     * the project owner, and workspace owners/admins.
     */
    public function assignableUsers()
    {
        $ids = $this->members()->pluck('users.id')->push($this->user_id);

        if ($this->workspace) {
            $ids = $ids->merge(
                $this->workspace->members()
                    ->wherePivotIn('role', ['owner', 'admin'])
                    ->pluck('users.id'),
            );
        }

        return User::query()
            ->whereIn('id', $ids->filter()->unique()->values())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /**
     * Clean up project data before deleting the project. There are no database
     * foreign keys on project-related tables, so this is done explicitly.
     */
    protected static function booted(): void
    {
        static::deleting(function (Project $project) {
            $taskIds = $project->tasks()->pluck('id');

            if ($taskIds->isNotEmpty()) {
                Attachment::whereIn('task_id', $taskIds)
                    ->get()
                    ->each(fn (Attachment $attachment) => Storage::disk('public')->delete($attachment->path));

                Comments::whereIn('task_id', $taskIds)->delete();
                Checklist::whereIn('task_id', $taskIds)->delete();
                Assignee::whereIn('task_id', $taskIds)->delete();
                Labels::whereIn('task_id', $taskIds)->delete();
                Attachment::whereIn('task_id', $taskIds)->delete();
            }

            $project->tasks()->delete();
            $project->boardLists()->delete();
            $project->memberRecords()->delete();
            $project->invitations()->delete();
        });
    }
}
