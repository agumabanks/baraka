<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountLockedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Locked - Security Alert',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-locked',
            with: [
                'user' => $this->user,
                'locked_until' => $this->user->locked_until,
                'minutes_remaining' => now()->diffInMinutes($this->user->locked_until, false),
            ],
        );
    }
}
