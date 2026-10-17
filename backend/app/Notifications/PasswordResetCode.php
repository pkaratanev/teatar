<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The 6-digit code the app asks for on the "forgotten password" screen. */
class PasswordResetCode extends Notification
{
    public function __construct(public readonly string $code) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Код за нова парола: {$this->code}")
            ->greeting('Здравейте,')
            ->line('Някой (надяваме се вие) поиска нова парола за профила ви в Teatar.')
            ->line("Вашият код: **{$this->code}**")
            ->line('Въведете го в приложението до 30 минути.')
            ->line('Ако не сте поискали нова парола, просто не обръщайте внимание на това писмо. Паролата ви не се променя.')
            ->salutation('Teatar');
    }
}
