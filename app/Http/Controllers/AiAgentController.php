<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use App\Services\AiAgentService;
use App\Services\AiSettings;
use App\Support\AiCapabilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAgentController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $configured = AiSettings::isConfigured();
        $model = AiSettings::model();
        $assistantName = AiSettings::name();
        $capabilitySentence = AiCapabilities::sentence();
        $workspaceCount = Workspace::forUser($user)->count();

        $projects = Project::visibleTo($user)
            ->with('workspace:id,name')
            ->orderBy('title')
            ->get(['id', 'title', 'workspace_id']);

        $tasks = Task::query()
            ->whereIn('project_id', $projects->pluck('id'))
            ->where('is_archive', 0)
            ->with(['project:id,title', 'list:id,title'])
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get(['id', 'title', 'project_id', 'list_id']);

        $mentionItems = collect()
            ->merge($projects->map(fn (Project $p) => [
                'type'     => 'project',
                'id'       => $p->id,
                'title'    => $p->title,
                'subtitle' => $p->workspace?->name ?? 'Workspace',
            ]))
            ->merge($tasks->map(fn (Task $t) => [
                'type'     => 'task',
                'id'       => $t->id,
                'title'    => $t->title,
                'subtitle' => trim(($t->project?->title ?? 'Project').($t->list ? ' · '.$t->list->title : '')),
            ]))
            ->values()
            ->all();

        return view('ai.index', compact(
            'configured',
            'model',
            'assistantName',
            'capabilitySentence',
            'workspaceCount',
            'mentionItems',
        ));
    }

    public function chat(Request $request, AiAgentService $agent): JsonResponse
    {
        $data = $request->validate([
            'message'         => 'required|string|max:4000',
            'mentions'        => 'nullable|array|max:20',
            'mentions.*.type' => 'required|in:project,task',
            'mentions.*.id'   => 'required|integer',
        ]);

        if (! AiSettings::isConfigured()) {
            return response()->json([
                'message' => 'AI is not configured. Add an OpenRouter API key in Admin → AI Agent.',
            ], 503);
        }

        $result = $agent->chat(
            $request->user(),
            $data['message'],
            $data['mentions'] ?? [],
        );

        return response()->json($result);
    }
}
