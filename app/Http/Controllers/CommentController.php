<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Comments;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        abort_unless($task->project && $request->user()->canAccessProject($task->project), 403);

        $data = $request->validate([
            'details' => 'required|string|max:5000',
        ]);

        $comment = $task->comments()->create([
            'user_id' => auth()->id() ?? 1,
            'details' => $data['details'],
        ]);

        $comment->load('user');

        return response()->json($comment, 201);
    }

    public function destroy(Request $request, Comments $comment): JsonResponse
    {
        abort_unless($comment->task?->project && $request->user()->canAccessProject($comment->task->project), 403);

        if ($comment->user_id && auth()->check() && $comment->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();
        return response()->json(['message' => 'Comment deleted']);
    }
}

