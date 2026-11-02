<?php

namespace App\Http\Controllers;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function __construct(private MembershipService $membership)
    {
    }

    /**
     * Create a workspace or project invitation.
     */
    public function store(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'email'        => 'required|email|max:255',
            'workspace_id' => 'nullable|integer|exists:workspaces,id',
            'project_id'   => 'nullable|integer|exists:projects,id',
            'role'         => 'nullable|string|in:admin,member,manager',
        ]);

        $project   = null;
        $workspace = null;

        if (! empty($data['project_id'])) {
            $project = Project::with('workspace')->findOrFail($data['project_id']);
            $workspace = $project->workspace;
            abort_unless($workspace && $request->user()->canManageProject($project), 403);
        } else {
            abort_if(empty($data['workspace_id']), 422, 'A workspace is required.');
            $workspace = Workspace::findOrFail($data['workspace_id']);
            abort_unless($request->user()->canManageWorkspace($workspace), 403);
        }

        $role = $data['role'] ?? 'member';
        if ($project) {
            $role = in_array($role, ['manager', 'member'], true) ? $role : 'member';
        } else {
            $role = in_array($role, ['admin', 'member'], true) ? $role : 'member';
        }

        $existingUser = User::whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])->first();

        if ($existingUser) {
            $alreadyInWorkspace = $workspace->hasMember($existingUser);
            $alreadyInProject   = $project ? $project->hasMember($existingUser) : true;

            if ($alreadyInWorkspace && $alreadyInProject) {
                return $this->respond(
                    $request,
                    ['message' => 'That person is already a member.'],
                    422,
                );
            }
        }

        $duplicate = Invitation::pending()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])
            ->where('workspace_id', $workspace->id)
            ->where('project_id', $project?->id)
            ->first();

        if ($duplicate) {
            $duplicate->update([
                'role'         => $role,
                'invited_by'   => $request->user()->id,
                'expires_at'   => now()->addDays(7),
            ]);

            $duplicate = $duplicate->fresh();
            $sent = $this->sendInvitationEmail($duplicate);

            return $this->respond($request, [
                'message'    => 'Invitation refreshed.',
                'invitation' => $duplicate,
                'accept_url' => $duplicate->accept_url,
                'email_sent' => $sent,
            ]);
        }

        $invitation = Invitation::create([
            'workspace_id' => $workspace->id,
            'project_id'   => $project?->id,
            'invited_by'   => $request->user()->id,
            'email'        => mb_strtolower($data['email']),
            'role'         => $role,
            'token'        => Str::random(48),
            'expires_at'   => now()->addDays(7),
        ]);

        $sent = $this->sendInvitationEmail($invitation);

        return $this->respond($request, [
            'message'    => 'Invitation created.',
            'invitation' => $invitation,
            'accept_url' => $invitation->accept_url,
            'email_sent' => $sent,
        ], 201);
    }

    /**
     * Public invitation landing page.
     */
    public function show(string $token): View
    {
        $invitation = Invitation::with(['workspace', 'project', 'inviter'])
            ->where('token', $token)
            ->firstOrFail();

        return view('invitations.show', compact('invitation'));
    }

    /**
     * Accept an invitation for the logged-in user.
     */
    public function accept(Request $request, string $token): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $invitation = Invitation::with(['workspace', 'project'])->where('token', $token)->firstOrFail();
        $user = $request->user();

        if ($invitation->isAccepted()) {
            return $this->acceptResponse($request, $invitation, 'This invitation was already accepted.');
        }

        if ($invitation->isExpired()) {
            return $this->acceptResponse($request, $invitation, 'This invitation has expired.', 410);
        }

        if (! $invitation->isForEmail($user->email)) {
            return $this->acceptResponse(
                $request,
                $invitation,
                'This invitation was sent to a different email address ('.$invitation->email.').',
                403,
            );
        }

        $needsProject = $this->membership->acceptInvitation($invitation, $user);

        $message = $needsProject
            ? 'You joined '.$invitation->workspace->name.'. Now choose a project to start working on.'
            : 'You joined '.$invitation->project->title.'.';

        return $this->acceptResponse($request, $invitation, $message, 200, $needsProject);
    }

    /**
     * Accept an invitation by pasting its code or full link.
     */
    public function paste(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string|max:500']);
        $code = trim($data['code']);

        // Allow the full accept URL to be pasted.
        if (str_contains($code, '/')) {
            $code = trim(parse_url($code, PHP_URL_PATH) ?? $code, '/');
            $code = last(explode('/', $code));
        }

        $invitation = Invitation::where('token', $code)->first();

        if (! $invitation) {
            return back()->withErrors(['code' => 'That invitation code is not valid.']);
        }

        return redirect()->route('invitations.show', $invitation->token);
    }

    /**
     * Revoke a pending invitation.
     */
    public function destroy(Request $request, Invitation $invitation): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $canManage = $invitation->project
            ? $request->user()->canManageProject($invitation->project)
            : $request->user()->canManageWorkspace($invitation->workspace);

        abort_unless($canManage, 403);

        $invitation->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Invitation revoked.']);
        }

        return back()->with('status', 'Invitation revoked.');
    }

    /**
     * Send the invitation email. Failures are logged but do not prevent the
     * invitation from being created, so the copyable link still works.
     */
    private function sendInvitationEmail(Invitation $invitation): bool
    {
        try {
            $invitation->loadMissing(['workspace', 'project', 'inviter']);

            Mail::to($invitation->email)->send(new InvitationMail($invitation));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to send invitation email.', [
                'invitation_id' => $invitation->id,
                'email'         => $invitation->email,
                'error'         => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function respond(Request $request, array $payload, int $status = 200): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json($payload, $status);
        }

        if ($status >= 400) {
            return back()->withErrors(['email' => $payload['message']])->withInput();
        }

        $message = $payload['message'];

        if (($payload['email_sent'] ?? false) && isset($payload['invitation'])) {
            $message .= ' An email was sent to '.$payload['invitation']->email.'.';
        }

        if (isset($payload['accept_url'])) {
            $message .= ' You can also share this link: '.$payload['accept_url'];
        }

        return back()->with('status', $message);
    }

    private function acceptResponse(
        Request $request,
        Invitation $invitation,
        string $message,
        int $status = 200,
        ?bool $needsProject = null,
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        $needsProject ??= $invitation->project_id === null;

        $redirect = ! $needsProject && $invitation->project
            ? route('projects.board', $invitation->project)
            : route('workspaces.join-project', $invitation->workspace);

        if ($status >= 400) {
            $redirect = route('invitations.show', $invitation->token);
        }

        if ($status >= 400 && ! $request->expectsJson()) {
            if ($status === 403) {
                abort(403, $message);
            }

            return redirect(route('invitations.show', $invitation->token))
                ->withErrors(['invitation' => $message]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message'  => $message,
                'redirect' => $redirect,
            ], $status);
        }

        return redirect($redirect)->with('status', $message);
    }
}
