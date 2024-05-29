<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CancelNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $event;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $event)
    {
        $this->user = $user;
        $this->event = $event;
    }


    public function build() {
        return $this->view('emails.cancel')
                    ->with([
                        'username' => $this->user->name,
                        'eventname' => $this->event->name,
                        'date' => $this->event->date,
                    ]);
    }
}
