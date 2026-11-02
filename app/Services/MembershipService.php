<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    /**
     * Add a user to a workspace. Keeps the invariant that the workspace owner
     * always has an owner-role membership row.
     */
    public function addToWorkspace(Workspace $workspace, User $user, string $role = 'member'): WorkspaceMember
    {
        if ($workspace->user_id === $user->id) {
            $role = 'owner';
        }

        $member = WorkspaceMember::firstOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $user->id],
            ['role' => $role],
        );

        if ($role === 'owner' && $member->role !== 'owner') {
            $member->update(['role' => 'owner']);
        } elseif ($role === 'admin' && $member->role === 'member') {
            $member->update(['role' => 'admin']);
        }

        return $member;
    }

    /**
     * Add a user to a project. Joining a project always includes joining the
     * project's workspace — this is the core rule of the invite system.
     */
    public function addToProject(Project $project, User $user, string $role = 'member'): ProjectMember
    {
        if ($project->workspace_id) {
            $this->addToWorkspace($project->workspace, $user);
        }

        $member = ProjectMember::firstOrCreate(
            ['project_id' => $project->id, 'user_id' => $user->id],
            ['role' => $role],
        );

        if ($role === 'manager' && $member->role === 'member') {
            $member->update(['role' => 'manager']);
        }

        return $member;
    }

    /**
     * Accept an invitation on behalf of a user.
     *
     * - Project invitations join the project *and* the workspace.
     * - Workspace invitations join the workspace only; the user is then
     *   redirected to pick a project in that workspace.
     *
     * @return bool Whether the user still needs to pick a project.
     */
    public function acceptInvitation(Invitation $invitation, User $user): bool
    {
        return DB::transaction(function () use ($invitation, $user) {
            $projectRole  = in_array($invitation->role, ['manager', 'member'], true)
                ? $invitation->role
                : 'member';
            $workspaceRole = in_array($invitation->role, ['admin', 'member'], true)
                ? $invitation->role
                : 'member';

            $this->addToWorkspace($invitation->workspace, $user, $workspaceRole);

            if ($invitation->project) {
                $this->addToProject($invitation->project, $user, $projectRole);
            }

            $invitation->update(['accepted_at' => now()]);

            return $invitation->project === null;
        });
    }
}
