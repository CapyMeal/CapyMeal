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
            ->subject('Confirmá tu email de CapyMeal 🍂')
            ->greeting('¡Hola! 🍂')
            ->line('Capi quiere confirmar que este email es realmente tuyo antes de guardar tu diario de comidas.')
            ->action('Confirmar mi email', $url)
            ->line('Este enlace vence en '.self::EXPIRE_MINUTES.' minutos.')
            ->line('Si vos no creaste esta cuenta, podés ignorar este email tranquilamente.')
            ->salutation('Con cariño, 🤎'."\n".'El equipo de CapyMeal');
    }
}
