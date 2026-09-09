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
        ]);

        $data['user_id'] = auth()->id() ?? 1;
        $data['order'] = $data['order'] ?? Task::where('list_id', $data['list_id'])->count() + 1;
        $data['slug'] = \Str::slug($data['title']) . '-' . time();

        $task = Task::create($data);
        $task->load(['assignees.user', 'comments', 'checklists', 'attachments', 'labels']);

        return response()->json($task, 201);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
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
        ]);

        $task->update($data);
        return response()->json($task);
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $items = $request->validate(['items' => 'required|array']);
        foreach ($items['items'] as $item) {
            Task::where('id', $item['id'])->update([
                'order'   => $item['order'],
                'list_id' => $item['list_id'] ?? null,
            ]);
        }
        return response()->json(['message' => 'Order updated']);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->update(['is_archive' => 1]);
        return response()->json(['message' => 'Task archived']);
    }

    public function show(Task $task): JsonResponse
    {
        $task->load(['assignees.user', 'comments.user', 'checklists', 'attachments', 'labels', 'list', 'project']);
        return response()->json($task);
    }
}
