<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function index(): View
    {
        $workspaces = Workspace::with('projects')->orderBy('name')->get();
        return view('workspaces.index', compact('workspaces'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string',
        ]);

        $data['user_id'] = auth()->id() ?? 1;
        $data['slug'] = \Str::slug($data['name']) . '-' . time();

        $workspace = Workspace::create($data);
        return response()->json($workspace, 201);
    }

    public function getProjects(Workspace $workspace): JsonResponse
    {
        $projects = $workspace->projects()->orderBy('title')->get();
        return response()->json($projects);
    }
}
