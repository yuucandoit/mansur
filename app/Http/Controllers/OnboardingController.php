<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Project;
use App\Models\Workspace;
use App\Services\MembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    /**
     * Entry point after login. Sends users to the right place:
     *  - No workspace yet  → onboarding (create / accept invite)
     *  - Workspace, no project → choose a project in that workspace
     *  - Has a project     → most recently updated project board
     */
    public function index(): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        $workspaces = Workspace::forUser($user)
            ->with(['projects' => fn ($q) => $q->visibleTo($user)])
            ->orderBy('name')
            ->get();

        $invitations = Invitation::pending()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($user->email)])
            ->with(['workspace', 'project', 'inviter'])
            ->latest()
            ->get();

        if ($workspaces->isEmpty()) {
            return view('onboarding.no-workspace', compact('user', 'invitations'));
        }

        $projects = Project::visibleTo($user)->get();

        if ($projects->isEmpty()) {
            return view('onboarding.no-project', compact('user', 'workspaces', 'invitations'));
        }

        return redirect()->route(
            'projects.board',
            $projects->sortByDesc('updated_at')->first(),
        );
    }

    /**
     * Workspace members who have not joined a project must pick one.
     */
    public function joinProject(Workspace $workspace): View|RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->canAccessWorkspace($workspace), 403);

        $projects = $workspace->projects()->orderBy('title')->get();
        $joinedProjectIds = $user->projects()->pluck('projects.id')->all();

        // If the workspace has projects and the user already belongs to one of
        // them, let them continue into the board.
        $available = $projects->whereNotIn('id', $joinedProjectIds);

        if ($projects->isNotEmpty() && $available->isEmpty()) {
            return redirect()->route('projects.board', $projects->first());
        }

        return view('onboarding.join-project', compact('user', 'workspace', 'projects', 'joinedProjectIds'));
    }

    public function storeJoinProject(Request $request, Workspace $workspace, MembershipService $membership): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canAccessWorkspace($workspace), 403);

        $data = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
        ]);

        $project = $workspace->projects()->findOrFail($data['project_id']);

        // Joining a project also guarantees workspace membership.
        $membership->addToProject($project, $user);

        return redirect()
            ->route('projects.board', $project)
            ->with('status', 'You joined '.$project->title.'.');
    }
}
