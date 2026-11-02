<?php

namespace App\Http\Controllers;

use App\Models\Labels;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        abort_unless(
            $task->project && $request->user()->canAccessProject($task->project),
            403,
        );

        $data = $this->validated($request);

        $label = $task->labels()->create([
            'name'  => $data['name'],
            'color' => $data['color'] ?? '#6366f1',
        ]);

        return response()->json($label, 201);
    }

    public function update(Request $request, Labels $label): JsonResponse
    {
        $task = $label->task;

        abort_unless(
            $task && $task->project && $request->user()->canAccessProject($task->project),
            403,
        );

        $data = $this->validated($request);

        $label->update([
            'name'  => $data['name'],
            'color' => $data['color'] ?? $label->color,
        ]);

        return response()->json($label);
    }

    public function destroy(Request $request, Labels $label): JsonResponse
    {
        $task = $label->task;

        abort_unless(
            $task && $task->project && $request->user()->canAccessProject($task->project),
            403,
        );

        $label->delete();

        return response()->json(['message' => 'Label deleted']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'  => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);
    }
}
