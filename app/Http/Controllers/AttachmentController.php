<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Task;
use App\Services\AttachmentCompressor;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function __construct(private AttachmentCompressor $compressor)
    {
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        abort_unless(
            $task->project && $request->user()->canAccessProject($task->project),
            403,
        );

        $request->validate([
            'file' => 'required|file|max:204800', // up to 200 MB
        ]);

        $file = $request->file('file');
        $result = $this->compressor->compressIfNeeded($file);

        $directory = 'attachments/'.$task->id;
        $extension = strtolower(pathinfo($result['name'], PATHINFO_EXTENSION));
        $extension = preg_match('/^[a-z0-9]+$/', $extension) ? $extension : 'bin';
        $filename = Str::uuid().'.'.$extension;

        if ($result['needs_compression'] && $result['temp_path']) {
            Storage::disk('public')->putFileAs($directory, new File($result['temp_path']), $filename);
            @unlink($result['temp_path']);
        } else {
            $file->storeAs($directory, $filename, 'public');
        }

        $attachment = $task->attachments()->create([
            'user_id' => $request->user()->id,
            'name'    => $result['name'],
            'path'    => $directory.'/'.$filename,
            'size'    => $result['size'],
            'width'   => $result['width'],
            'height'  => $result['height'],
        ]);

        $attachment->load('user');

        return response()->json([
            'attachment'       => $attachment,
            'compressed'       => $result['needs_compression'],
            'original_size'    => (int) $file->getSize(),
        ], 201);
    }

    public function destroy(Request $request, Attachment $attachment): JsonResponse
    {
        $task = $attachment->task;

        abort_unless(
            $task && $task->project && $request->user()->canAccessProject($task->project),
            403,
        );

        $canDelete = $attachment->user_id === $request->user()->id
            || $request->user()->canManageProject($task->project);

        abort_unless($canDelete, 403);

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted']);
    }
}
