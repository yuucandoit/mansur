<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'task_notifications', 'compact_cards'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active'          => 'boolean',
            'task_notifications' => 'boolean',
            'compact_cards'      => 'boolean',
        ];
    }

    /** Workspaces this user belongs to (owned or invited). */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    /** Projects this user belongs to (owned or invited). */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'email', 'email')
            ->pending()
            ->latest();
    }

    /** Global platform role: admin or user. */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        // Newly created model instances may not have the DB default hydrated yet.
        return $this->is_active === null ? true : (bool) $this->is_active;
    }

    public function isRegularUser(): bool
    {
        return $this->role === 'user';
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /** Workspace role: owner, admin, member or null. */
    public function workspaceRole(Workspace $workspace): ?string
    {
        return $workspace->roleOf($this);
    }

    public function canAccessWorkspace(Workspace $workspace): bool
    {
        return $this->workspaceRole($workspace) !== null;
    }

    /** Owners and admins may manage workspace members and invitations. */
    public function canManageWorkspace(Workspace $workspace): bool
    {
        return $workspace->isManagedBy($this);
    }

    /** Only the workspace owner may delete the workspace. */
    public function canDeleteWorkspace(Workspace $workspace): bool
    {
        return $workspace->user_id === $this->id;
    }

    /** Only the workspace owner may delete projects inside that workspace. */
    public function canDeleteProject(Project $project): bool
    {
        if ($project->workspace) {
            return $project->workspace->user_id === $this->id;
        }

        return $project->user_id === $this->id;
    }

    /** Project membership, or workspace ownership/admin grants project access. */
    public function canAccessProject(Project $project): bool
    {
        if ($project->user_id === $this->id || $project->hasMember($this)) {
            return true;
        }

        return $project->workspace && $project->workspace->isManagedBy($this);
    }

    public function canManageProject(Project $project): bool
    {
        if ($project->user_id === $this->id) {
            return true;
        }

        if ($project->roleOf($this) === 'manager') {
            return true;
        }

        return $project->workspace && $project->workspace->isManagedBy($this);
    }
}
