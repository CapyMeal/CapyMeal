<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5174'), '/');
        $url = $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Recuperá tu contraseña – CapyMeal')
            ->view('emails.reset-password', [
                'url'      => $url,
                'userName' => $notifiable->name,
            ]);
    }
}
