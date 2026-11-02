<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invitation $invitation)
    {
    }

    public function envelope(): Envelope
    {
        $subject = $this->invitation->project
            ? 'You are invited to '.$this->invitation->project->title
            : 'You are invited to '.$this->invitation->workspace->name;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invitations.invite');
    }
}
