<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminRoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        Role::create($this->validated($request));

        return back()->with('status', 'Legacy role created.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $role->update($this->validated($request));

        return back()->with('status', 'Legacy role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $role->delete();

        return back()->with('status', 'Legacy role deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'slug'             => 'required|string|max:100',
            'create_workspace' => 'nullable|boolean',
            'create_project'   => 'nullable|boolean',
            'access'           => 'nullable|string',
        ]);

        $access = null;

        if (! empty($data['access'])) {
            $decoded = json_decode($data['access'], true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'access' => 'The access field must be valid JSON.',
                ]);
            }

            $access = $decoded;
        }

        return [
            'name'             => $data['name'],
            'slug'             => $data['slug'],
            'create_workspace' => (bool) ($data['create_workspace'] ?? false),
            'create_project'   => (bool) ($data['create_project'] ?? false),
            'access'           => $access,
        ];
    }
}
