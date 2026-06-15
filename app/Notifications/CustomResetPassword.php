<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPassword
{
    public function toMail($notifiable)
    {
        if ($notifiable->role === 'admin') {
            $url = url(config('app.url') . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email));
        } else {
            $url = 'https://mavii.my.id/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);
        }

        return (new MailMessage)
            ->subject('Reset Password')
            ->line('Anda menerima email ini karena kami menerima permintaan reset password untuk akun Anda.')
            ->action('Reset Password', $url)
            ->line('Link ini akan kadaluarsa dalam ' . config('auth.passwords.users.expire') . ' menit.')
            ->line('Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini.');
    }
}