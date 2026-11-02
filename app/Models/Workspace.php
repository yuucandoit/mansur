<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'user_id'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
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
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function memberRecords(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
        });
    }

    public function hasMember(User $user): bool
    {
        return $this->user_id === $user->id
            || $this->members()->where('users.id', $user->id)->exists();
    }

    public function isManagedBy(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        return $this->members()
            ->where('users.id', $user->id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function roleOf(User $user): ?string
    {
        if ($this->user_id === $user->id) {
            return 'owner';
        }

        $member = $this->members()->where('users.id', $user->id)->first();

        return $member?->pivot?->role;
    }

    /**
     * Delete all projects (and their tasks) before removing the workspace.
     */
    protected static function booted(): void
    {
        static::deleting(function (Workspace $workspace) {
            $workspace->projects()->get()->each->delete();
            $workspace->invitations()->delete();
            $workspace->memberRecords()->delete();
        });
    }
}
