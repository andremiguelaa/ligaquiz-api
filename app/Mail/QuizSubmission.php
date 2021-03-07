<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuizSubmission extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $questions, $media, $submittedAnswers)
    {
        $this->user = $user;
        $this->questions = $questions;
        $this->media = $media;
        $this->submittedAnswers = $submittedAnswers;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.quiz_submission')
            ->subject(__('mails.quiz_submission_subject'))
            ->with([
                'user' => $this->user,
                'questions' => $this->questions,
                'media' => $this->media,
                'submittedAnswers' => $this->submittedAnswers,
            ]);
    }
}
