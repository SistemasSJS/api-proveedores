<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $url;
    public $userName;

    public function __construct($url, $userName = null)
    {
        $this->url = $url;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->subject('Recuperación de Contraseña - SJS Construcciones')
            ->view('emails.password-reset')
            ->attach(public_path('/assets/icon/ms-310.png'), [
                'as' => 'logo.png',
                'mime' => 'image/png',
            ]);
    }
}
