<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\BoardList;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'      => 'required|string|max:200',
            'list_id'    => 'required|integer',
            'project_id' => 'required|integer',
            'order'      => 'nullable|integer',
            'description'=> 'nullable|string',
            'due_date'   => 'nullable|date',
            'priority'   => 'nullable|in:low,medium,high,critical',
            'parent_id'  => 'nullable|integer',
        ]);

        $project = Project::findOrFail($data['project_id']);
        abort_unless($request->user()->canAccessProject($project), 403);

        $list = BoardList::where('project_id', $project->id)->findOrFail($data['list_id']);

        $data['user_id'] = auth()->id() ?? 1;
        $data['order'] = $data['order'] ?? Task::where('list_id', $list->id)->count() + 1;
        $data['slug'] = \Str::slug($data['title']) . '-' . time();

        $task = Task::create($data);
        $task->load(['assignees.user', 'comments', 'checklists', 'attachments', 'labels']);

        return response()->json($task, 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        abort_unless($task->project && $request->user()->canAccessProject($task->project), 403);

        $data = $request->validate([
            'title'      => 'sometimes|string|max:200',
            'description'=> 'nullable|string',
            'list_id'    => 'nullable|integer',
            'order'      => 'nullable|integer',
            'due_date'   => 'nullable|date',
            'is_done'    => 'nullable|boolean',
            'is_archive' => 'nullable|boolean',
            'priority'   => 'nullable|in:low,medium,high,critical',
            'is_critical'=> 'nullable|boolean',
            'parent_id'  => 'nullable|integer',
        ]);

        $task->update($data);
        $task->load(['assignees.user', 'comments.user', 'checklists', 'attachments', 'labels', 'list', 'project', 'parent', 'children.list']);
        return response()->json($task);
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $items = $request->validate(['items' => 'required|array']);
        foreach ($items['items'] as $item) {
            $task = Task::find($item['id']);
            if ($task) {
                abort_unless($task->project && $request->user()->canAccessProject($task->project), 403);
            }

            Task::where('id', $item['id'])->update([
                'order'   => $item['order'],
                'list_id' => $item['list_id'] ?? null,
            ]);
        }
        return response()->json(['message' => 'Order updated']);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        abort_unless($task->project && $request->user()->canAccessProject($task->project), 403);

        $task->update(['is_archive' => 1]);
        return response()->json(['message' => 'Task archived']);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        abort_unless($task->project && $request->user()->canAccessProject($task->project), 403);

        $task->load([
            'assignees.user',
            'assignees.assigner',
            'comments' => function($q) {
                $q->with('user')->orderBy('created_at', 'asc');
            },
            'checklists',
            'attachments.user',
            'labels',
            'list',
            'project',
            'parent',
            'children' => function($q) {
                $q->where('is_archive', 0)->with('list');
            }
        ]);

        // Candidate parent tasks in the same project (not self, and not its children)
        $potentialParents = Task::where('project_id', $task->project_id)
            ->where('is_archive', 0)
            ->where('id', '!=', $task->id)
            ->where(function($q) use ($task) {
                $q->whereNull('parent_id')->orWhere('parent_id', '!=', $task->id);
            })
            ->select('id', 'title', 'list_id')
            ->orderBy('title')
            ->get();

        // Also fetch lists in this project so the user can easily change columns
        $boardLists = BoardList::where('project_id', $task->project_id)
            ->where('is_archive', 0)
            ->orderBy('order')
            ->select('id', 'title')
            ->get();

        $task->setRelation('potential_parents', $potentialParents);
        $task->setRelation('project_lists', $boardLists);
        $task->setRelation('assignable_users', $task->project->assignableUsers());
        $task->setAttribute('can_manage', $request->user()->canManageProject($task->project));

        return response()->json($task);
    }
}
