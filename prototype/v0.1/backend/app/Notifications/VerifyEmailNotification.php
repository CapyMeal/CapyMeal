<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    private const EXPIRE_MINUTES = 60;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // El link apunta al backend, no al frontend: se abre desde el
        // cliente de correo, en cualquier dispositivo -- no necesariamente
        // el que tiene la sesión abierta. EmailVerificationController lo
        // valida y recién ahí redirige al frontend.
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(self::EXPIRE_MINUTES),
            ['id' => $notifiable->id, 'hash' => sha1($notifiable->email)]
        );

        return (new MailMessage)
            ->subject(__('messages.mail_verify_subject'))
            ->greeting(__('messages.mail_greeting'))
            ->line(__('messages.mail_verify_line'))
            ->action(__('messages.mail_verify_action'), $url)
            ->line(__('messages.mail_link_expire', ['minutes' => self::EXPIRE_MINUTES]))
            ->line(__('messages.mail_verify_ignore'))
            ->salutation(__('messages.mail_salutation'));
    }
}
