<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invitation->project ? 'Project invitation' : 'Workspace invitation' }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#18181b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f4f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e4e4e7;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px 8px 32px;">
                            <div style="font-size:13px;font-weight:700;letter-spacing:1px;color:#71717a;text-transform:uppercase;">ProTask</div>
                            <h1 style="margin:10px 0 0 0;font-size:22px;line-height:1.3;color:#18181b;">
                                You are invited to {{ $invitation->project ? 'a project' : 'a workspace' }}
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:12px 32px 0 32px;font-size:14px;line-height:1.6;color:#52525b;">
                            <p style="margin:0 0 14px 0;">
                                Hi,
                            </p>
                            <p style="margin:0 0 16px 0;">
                                <strong style="color:#18181b;">{{ $invitation->inviter?->name ?? 'A teammate' }}</strong>
                                invited you to collaborate on
                                <strong style="color:#18181b;">{{ $invitation->project?->title ?? $invitation->workspace->name }}</strong>
                                @if($invitation->project)
                                in the <strong style="color:#18181b;">{{ $invitation->workspace->name }}</strong> workspace
                                @endif
                                as a <strong style="color:#18181b;">{{ $invitation->role }}</strong>.
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:22px 0;">
                                <tr>
                                    <td style="border-radius:8px;background:#18181b;">
                                        <a href="{{ $invitation->accept_url }}"
                                           style="display:inline-block;padding:12px 22px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;border-radius:8px;">
                                            Accept invitation
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px 0;font-size:12px;color:#71717a;">
                                Or paste this link into your browser:
                            </p>
                            <p style="margin:0 0 20px 0;font-size:12px;word-break:break-all;">
                                <a href="{{ $invitation->accept_url }}" style="color:#4f46e5;">{{ $invitation->accept_url }}</a>
                            </p>

                            <p style="margin:0 0 20px 0;font-size:12px;color:#71717a;">
                                This invitation was sent to <strong>{{ $invitation->email }}</strong>.
                                @if($invitation->expires_at)
                                It expires on {{ $invitation->expires_at->format('M j, Y') }}.
                                @endif
                            </p>

                            @if($invitation->project)
                            <p style="margin:0 0 20px 0;font-size:12px;color:#71717a;">
                                Accepting this project invitation also adds you to the
                                <strong>{{ $invitation->workspace->name }}</strong> workspace.
                            </p>
                            @else
                            <p style="margin:0 0 20px 0;font-size:12px;color:#71717a;">
                                After joining the workspace, you will choose a project to start working on.
                            </p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 32px 28px 32px;border-top:1px solid #f4f4f5;font-size:11px;color:#a1a1aa;">
                            If you were not expecting this invitation, you can safely ignore this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
