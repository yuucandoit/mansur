<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminBackupController extends Controller
{
    public function index(): View
    {
        $backups = Backup::latest()->get();

        return view('admin.backups.index', compact('backups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:100',
        ]);

        $name = $data['name'] ?? 'backup-'.now()->format('Ymd-His');
        $name = preg_replace('/[^A-Za-z0-9_\-]/', '-', $name);

        $snapshot = [
            'generated_at' => now()->toIso8601String(),
            'users'        => User::query()
                ->select('id', 'name', 'email', 'role', 'is_active', 'created_at')
                ->get()
                ->toArray(),
            'workspaces'   => Workspace::query()->get()->toArray(),
            'projects'     => Project::query()->get()->toArray(),
            'tasks'        => Task::query()->get()->toArray(),
        ];

        $json = json_encode($snapshot, JSON_PRETTY_PRINT);
        $path = 'backups/'.$name.'.json';

        Storage::disk('local')->put($path, $json);

        Backup::create([
            'name'    => $name,
            'details' => [
                'path'   => $path,
                'size'   => strlen($json),
                'counts' => [
                    'users'      => count($snapshot['users']),
                    'workspaces' => count($snapshot['workspaces']),
                    'projects'   => count($snapshot['projects']),
                    'tasks'      => count($snapshot['tasks']),
                ],
            ],
        ]);

        return back()->with('status', 'Backup created.');
    }

    public function download(Backup $backup): StreamedResponse
    {
        $path = $backup->details['path'] ?? null;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $backup->name.'.json');
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        $path = $backup->details['path'] ?? null;

        if ($path) {
            Storage::disk('local')->delete($path);
        }

        $backup->delete();

        return back()->with('status', 'Backup deleted.');
    }
}
