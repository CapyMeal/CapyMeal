<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(protected string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url'), '/')
            .'/reset-password?token='.$this->token
            .'&email='.urlencode($notifiable->email);

        $expireMinutes = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject(__('messages.mail_reset_subject'))
            ->greeting(__('messages.mail_greeting'))
            ->line(__('messages.mail_reset_line'))
            ->action(__('messages.mail_reset_action'), $url)
            ->line(__('messages.mail_link_expire', ['minutes' => $expireMinutes]))
            ->line(__('messages.mail_reset_ignore'))
            ->salutation(__('messages.mail_salutation'));
    }
}
