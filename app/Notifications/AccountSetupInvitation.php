<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountSetupInvitation extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the account setup mail representation.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if (! $notifiable instanceof User) {
            throw new \LogicException('Account setup invitations may only be sent to users.');
        }

        return (new MailMessage)
            ->subject('Set up your '.config('app.name').' account')
            ->greeting('Welcome, '.$notifiable->name.'!')
            ->line('An administrator has created an account for you.')
            ->line('Choose a password to finish setting up your account.')
            ->action('Set up your account', route('password.reset', [
                'token' => $this->token,
                'email' => $notifiable->email,
                'setup' => 1,
            ]))
            ->line('This setup link expires in 60 minutes.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
