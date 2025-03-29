<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ReadingReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Необходимо е незабавно въвеждане на показания за '.now()->translatedFormat('F.Y').' г.',
        );
    }

    public function content(): Content
    {
        // Get Bulgarian month name
        $monthNames = [
            '1' => 'януари',
            '2' => 'февруари',
            '3' => 'март',
            '4' => 'април',
            '5' => 'май',
            '6' => 'юни',
            '7' => 'юли',
            '8' => 'август',
            '9' => 'септември',
            '10' => 'октомври',
            '11' => 'ноември',
            '12' => 'декември',
        ];

        $currentMonthNumber = now()->format('n');
        $currentMonthName = $monthNames[$currentMonthNumber];

        return new Content(
            markdown: 'emails.readings.reminder',
            with: [
                'user' => $this->user,
                'currentMonthName' => $currentMonthName,
                'url' => URL::route('readings.history'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
