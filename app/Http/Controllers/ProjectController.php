<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\Workspace;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private MembershipService $membership)
    {
    }

    /**
     * Shared helper to load project details, active lists, and tasks
     */
    protected function getProjectData(Project $project): array
    {
        $user = auth()->user();
        abort_unless($user->canAccessProject($project), 403);

        $project->load('workspace');
        $boardLists = $project->activeLists()
            ->with(['tasks' => function ($q) {
                $q->where('is_archive', 0)
                  ->orderBy('order')
                  ->with(['assignees.user', 'comments', 'checklists', 'attachments', 'labels']);
            }])
            ->get();

        // Assign color to each list
        $colors = Project::$columnColors;
        $boardLists->each(function ($list, $i) use ($colors) {
            $list->color = $colors[$i % count($colors)];
        });

        $workspaces = Workspace::forUser($user)
            ->withCount('projects')
            ->with(['projects' => fn ($q) => $q->visibleTo($user)])
            ->orderBy('name')
            ->get();

        $allTasks = $boardLists->flatMap->tasks;

        return compact('project', 'boardLists', 'workspaces', 'allTasks');
    }

    public function index(): View
    {
        $user = auth()->user();

        $workspaces = Workspace::forUser($user)
            ->withCount('projects')
            ->with(['projects' => fn ($q) => $q->visibleTo($user)])
            ->orderBy('name')
            ->get();

        $projects = Project::visibleTo($user)
            ->with('workspace')
            ->orderByDesc('updated_at')
            ->get();

        return view('projects.index', compact('workspaces', 'projects'));
    }

    public function board(Project $project): View
    {
        return view('projects.board', $this->getProjectData($project));
    }

    public function list(Project $project): View
    {
        return view('projects.list', $this->getProjectData($project));
    }

    public function gantt(Project $project): View
    {
        return view('projects.gantt', $this->getProjectData($project));
    }

    public function calendar(Project $project): View
    {
        return view('projects.calendar', $this->getProjectData($project));
    }

    public function table(Project $project): View
    {
        return view('projects.table', $this->getProjectData($project));
    }

    /**
     * Project members and pending project invitations.
     */
    public function members(Project $project): View
    {
        $user = auth()->user();
        abort_unless($user->canAccessProject($project), 403);

        $project->load('workspace');
        $members = $project->members()->orderBy('name')->get();
        $invitations = $project->invitations()
            ->pending()
            ->with(['workspace', 'inviter'])
            ->latest()
            ->get();

        $canManage = $user->canManageProject($project);

        return view('projects.members', compact('project', 'members', 'invitations', 'canManage'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'        => 'required|string|max:200',
            'workspace_id' => 'nullable|integer|exists:workspaces,id',
            'description'  => 'nullable|string',
            'color'        => 'nullable|string|max:20',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date',
        ]);

        $user = $request->user();

        if (! empty($data['workspace_id'])) {
            $workspace = Workspace::findOrFail($data['workspace_id']);
            abort_unless($user->canAccessWorkspace($workspace), 403);
        } else {
            // No workspace selected: fall back to (and create if needed) the
            // user's personal workspace so every project lives in a workspace.
            $workspace = Workspace::firstOrCreate(
                ['user_id' => $user->id, 'slug' => 'personal-'.$user->id],
                [
                    'name'        => $user->name.'\'s Workspace',
                    'description' => 'Personal workspace',
                ],
            );
            $this->membership->addToWorkspace($workspace, $user, 'owner');
        }

        $data['workspace_id'] = $workspace->id;
        $data['user_id'] = $user->id;
        $project = Project::create($data);

        // The creator joins the project (and the workspace).
        $this->membership->addToProject($project, $user, 'manager');

        // Create default board lists
        $defaultLists = ['To Do', 'On Progress', 'Revision', 'Done'];
        foreach ($defaultLists as $i => $title) {
            BoardList::create([
                'title'      => $title,
                'project_id' => $project->id,
                'user_id'    => $data['user_id'],
                'order'      => $i,
                'is_done'    => $title === 'Done' ? 1 : 0,
            ]);
        }

        return response()->json($project, 201);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        abort_unless($request->user()->canAccessProject($project), 403);

        $data = $request->validate([
            'title'       => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'is_starred'  => 'nullable|boolean',
        ]);

        $project->update($data);
        return response()->json($project);
    }

    public function destroy(Request $request, Project $project): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()->canDeleteProject($project), 403);

        $title = $project->title;
        $project->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Project deleted']);
        }

        return redirect()->route('projects.index')
            ->with('status', $title.' was deleted.');
    }

    public function toggleStar(Request $request, Project $project): JsonResponse
    {
        abort_unless($request->user()->canAccessProject($project), 403);

        $project->update(['is_starred' => !$project->is_starred]);
        return response()->json(['is_starred' => $project->is_starred]);
    }
}
