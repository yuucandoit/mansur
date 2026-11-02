<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * List every registered user with their global role.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $role = $request->query('role');
        $status = $request->query('status');

        $users = User::query()
            ->withCount(['workspaces', 'projects'])
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.mb_strtolower($search).'%';

                $query->where(function ($q) use ($term) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
                });
            })
            ->when(in_array($role, ['admin', 'user'], true), fn ($q) => $q->where('role', $role))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all'      => User::count(),
            'admin'    => User::where('role', 'admin')->count(),
            'user'     => User::where('role', 'user')->count(),
            'active'   => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        return view('admin.users', compact('users', 'search', 'role', 'status', 'counts'));
    }

    /**
     * Change a user's global role.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => 'required|string|in:admin,user',
        ]);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        if ($user->isAdmin()
            && $data['role'] === 'user'
            && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'At least one administrator must remain.']);
        }

        $user->update(['role' => $data['role']]);

        return back()->with(
            'status',
            $user->name.' is now '.($data['role'] === 'admin' ? 'an administrator' : 'a regular user').'.',
        );
    }

    /**
     * Update any user's password without requiring their current password.
     */
    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update(['password' => $data['password']]);

        return back()->with('status', 'Password updated for '.$user->name.'.');
    }

    /**
     * Activate or deactivate an account.
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $isActive = (bool) $data['is_active'];

        if ($user->id === $request->user()->id && ! $isActive) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }

        if (! $isActive
            && $user->isAdmin()
            && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['user' => 'At least one active administrator must remain.']);
        }

        $user->update(['is_active' => $isActive]);

        return back()->with(
            'status',
            $user->name.' has been '.($isActive ? 'activated' : 'deactivated').'.',
        );
    }

    /**
     * Delete a user account.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account from here.']);
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['user' => 'At least one administrator must remain.']);
        }

        $name = $user->name;
        $user->delete();

        return back()->with('status', $name.'\'s account was deleted.');
    }
}
