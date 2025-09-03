<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    // 👇 No declares $locale aquí

    public function __construct(?string $locale = null)
    {
        if ($locale !== null) {
            // Usa la propiedad pública heredada de Notification
            $this->locale = $locale;
        }
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $this->locale ?? ($notifiable->locale ?? config('app.locale'));

        return (new MailMessage)
            ->subject(__('notifications.welcome.subject', ['app' => config('app.name')], $locale))
            ->greeting(__('notifications.welcome.greeting', ['name' => $notifiable->name ?? ''], $locale))
            ->line(__('notifications.welcome.line1', ['app' => config('app.name')], $locale))
            ->action(__('notifications.welcome.action', [], $locale), url('/dashboard'))
            ->line(__('notifications.welcome.line2', [], $locale))
            ->salutation(__('notifications.welcome.salutation', ['app' => config('app.name')], $locale));
    }

    public function toArray(object $notifiable): array
    {
        $locale = $this->locale ?? ($notifiable->locale ?? config('app.locale'));

        return [
            'key'    => 'welcome',
            'params' => ['app' => config('app.name')],
            'url'    => url('/dashboard'),
            'locale' => $locale,
        ];
    }
}
