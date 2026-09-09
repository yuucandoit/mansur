<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\BoardList;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $workspaces = Workspace::with('projects')->orderBy('name')->get();
        $projects = Project::with('workspace')->orderByDesc('updated_at')->get();
        return view('projects.index', compact('workspaces', 'projects'));
    }

    public function show(Project $project): View
    {
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

        $workspaces = Workspace::with('projects')->orderBy('name')->get();

        return view('projects.board', compact('project', 'boardLists', 'workspaces'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'        => 'required|string|max:200',
            'workspace_id' => 'nullable|integer',
            'description'  => 'nullable|string',
            'color'        => 'nullable|string|max:20',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date',
        ]);

        $data['user_id'] = auth()->id() ?? 1;
        $project = Project::create($data);

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
        $data = $request->validate([
            'title'       => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'is_starred'  => 'nullable|boolean',
        ]);

        $project->update($data);
        return response()->json($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();
        return response()->json(['message' => 'Project deleted']);
    }

    public function toggleStar(Project $project): JsonResponse
    {
        $project->update(['is_starred' => !$project->is_starred]);
        return response()->json(['is_starred' => $project->is_starred]);
    }
}
