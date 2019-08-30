<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetRequest extends Notification
{
    use Queueable;

    protected $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail()
    {
        $url = env('SPA_URL').'/reset-password/'.$this->token;

        return (new MailMessage())
            ->subject(__('notifications.reset_password_subject'))
            ->line(__('notifications.reset_password_intro'))
            ->action(__('notifications.reset_password_button'), $url)
            ->line(__('notifications.reset_password_disclaimer'));
    }
}
