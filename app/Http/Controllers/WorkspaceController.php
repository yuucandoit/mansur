<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\Project;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function __construct(private MembershipService $membership)
    {
    }

    public function index(): View
    {
        $user = auth()->user();

        $workspaces = Workspace::forUser($user)
            ->with(['projects' => fn ($q) => $q->visibleTo($user)])
            ->orderBy('name')
            ->get();

        return view('workspaces.index', compact('workspaces'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string',
        ]);

        $user = $request->user();

        $data['user_id'] = $user->id;
        $data['slug'] = Str::slug($data['name']) . '-' . time();

        $workspace = Workspace::create($data);

        // The creator is always the owner and a member.
        $this->membership->addToWorkspace($workspace, $user, 'owner');

        return response()->json($workspace, 201);
    }

    /**
     * Workspace members, pending invitations, and invite controls.
     */
    public function members(Workspace $workspace): View
    {
        $user = auth()->user();
        abort_unless($user->canAccessWorkspace($workspace), 403);

        // Make sure the owner row always exists in the member list.
        if ($workspace->user_id && ! $workspace->memberRecords()->where('user_id', $workspace->user_id)->exists()) {
            $this->membership->addToWorkspace($workspace, $workspace->owner, 'owner');
        }

        $members = $workspace->members()->orderBy('name')->get();
        $projects = $workspace->projects()->orderBy('title')->get();
        $invitations = $workspace->invitations()
            ->pending()
            ->with(['project', 'inviter'])
            ->latest()
            ->get();

        $canManage = $user->canManageWorkspace($workspace);

        return view('workspaces.members', compact(
            'workspace',
            'members',
            'projects',
            'invitations',
            'canManage',
        ));
    }

    public function updateMemberRole(Request $request, Workspace $workspace, User $user): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        abort_unless($request->user()->canManageWorkspace($workspace), 403);
        abort_if($workspace->user_id === $user->id, 422, 'The workspace owner role cannot be changed.');

        $data = $request->validate([
            'role' => 'required|string|in:admin,member',
        ]);

        $workspace->memberRecords()->updateOrCreate(
            ['user_id' => $user->id],
            ['role' => $data['role']],
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Member role updated.']);
        }

        return back()->with('status', 'Member role updated.');
    }

    public function removeMember(Request $request, Workspace $workspace, User $user): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        abort_unless($request->user()->canManageWorkspace($workspace), 403);
        abort_if($workspace->user_id === $user->id, 422, 'The workspace owner cannot be removed.');

        \Illuminate\Support\Facades\DB::transaction(function () use ($workspace, $user) {
            $workspace->memberRecords()->where('user_id', $user->id)->delete();

            // Remove the user from every project in this workspace as well.
            \App\Models\ProjectMember::whereIn(
                'project_id',
                $workspace->projects()->pluck('id'),
            )->where('user_id', $user->id)->delete();
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Member removed from workspace.']);
        }

        return back()->with('status', $user->name.' was removed from the workspace.');
    }

    /**
     * Delete the workspace and everything inside it. Owner only.
     */
    public function destroy(Request $request, Workspace $workspace): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()->canDeleteWorkspace($workspace), 403);

        $name = $workspace->name;
        $workspace->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Workspace deleted.']);
        }

        return redirect()->route('onboarding')
            ->with('status', $name.' and all of its projects were deleted.');
    }

    public function getProjects(Workspace $workspace): JsonResponse
    {
        abort_unless(auth()->user()->canAccessWorkspace($workspace), 403);

        $projects = $workspace->projects()->orderBy('title')->get();

        return response()->json($projects);
    }
}
