<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Reminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($type, $user, $opponent, $quiz, $special_quiz)
    {
        $this->type = $type;
        $this->user = $user;
        $this->opponent = $opponent;
        $this->quiz = $quiz;
        $this->special_quiz = $special_quiz;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.reminder')
            ->subject(__('mails.reminder_subject'))
            ->with([
                'type' => $this->type,
                'user' => $this->user,
                'opponent' => $this->opponent,
                'quiz' => $this->quiz,
                'special_quiz' => $this->special_quiz,
            ]);
    }
}
