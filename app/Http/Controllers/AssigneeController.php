<?php

namespace App\Http\Controllers;

use App\Models\Assignee;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssigneeController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        abort_unless(
            $task->project && $request->user()->canAccessProject($task->project),
            403,
        );

        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::findOrFail($data['user_id']);

        abort_unless(
            $task->project->assignableUsers()->contains('id', $user->id),
            422,
            'This user cannot be assigned to this project.',
        );

        $assignee = Assignee::firstOrCreate(
            ['task_id' => $task->id, 'user_id' => $user->id],
            ['assigned_by' => $request->user()->id],
        );

        if (! $assignee->assigned_by) {
            $assignee->update(['assigned_by' => $request->user()->id]);
        }

        $assignee->load(['user', 'assigner']);

        return response()->json($assignee, 201);
    }

    public function destroy(Request $request, Assignee $assignee): JsonResponse
    {
        $task = $assignee->task;

        abort_unless(
            $task && $task->project && $request->user()->canAccessProject($task->project),
            403,
        );

        $canRemove = $assignee->assigned_by === $request->user()->id
            || $assignee->user_id === $request->user()->id
            || $request->user()->canManageProject($task->project);

        abort_unless($canRemove, 403);

        $assignee->delete();

        return response()->json(['message' => 'Assignee removed']);
    }
}
