<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Checklist;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChecklistController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        abort_unless($task->project && $request->user()->canAccessProject($task->project), 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $checklist = $task->checklists()->create([
            'title'   => $data['title'],
            'is_done' => 0,
        ]);

        return response()->json($checklist, 201);
    }

    public function update(Request $request, Checklist $checklist): JsonResponse
    {
        abort_unless($checklist->task?->project && $request->user()->canAccessProject($checklist->task->project), 403);

        $data = $request->validate([
            'title'   => 'sometimes|string|max:255',
            'is_done' => 'sometimes|boolean',
        ]);

        $checklist->update($data);
        return response()->json($checklist);
    }

    public function destroy(Request $request, Checklist $checklist): JsonResponse
    {
        abort_unless($checklist->task?->project && $request->user()->canAccessProject($checklist->task->project), 403);

        $checklist->delete();
        return response()->json(['message' => 'Checklist item deleted']);
    }
}

