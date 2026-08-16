<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    public function __construct(protected string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url'), '/')
            . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($notifiable->email);

        $expireMinutes = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Recuperá tu contraseña de CapyMeal 🍂')
            ->greeting('¡Hola! 🍂')
            ->line('Capi recibió un pedido para restablecer la contraseña de tu cuenta de CapyMeal.')
            ->action('Elegir nueva contraseña', $url)
            ->line("Este enlace vence en {$expireMinutes} minutos.")
            ->line('Si vos no pediste esto, podés ignorar este email tranquilamente — tu contraseña sigue siendo la misma.')
            ->salutation('Con cariño, 🤎' . "\n" . 'El equipo de CapyMeal');
    }
}
