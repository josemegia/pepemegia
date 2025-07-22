<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;

class CustomVerifyEmail extends BaseVerifyEmail
{
    public function toMail($notifiable)
    {
        // Clona el idioma del usuario, si existe (ej: 'es', 'en', etc.)
        $locale = $notifiable->locale ?? config('app.locale');
        App::setLocale($locale);

        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject(__('email.verify_subject', ['app' => config('app.name')]))
            ->greeting(__('email.greeting', ['app' => config('app.name')]))
            ->line(__('email.verify_line1'))
            ->action(__('email.verify_action'), $verificationUrl)
            ->line(__('email.verify_line2'))
            ->salutation(__('email.salutation', ['app' => config('app.name')]));
    }
}
