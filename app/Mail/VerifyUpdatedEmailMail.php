<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyUpdatedEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public $url;
    public $userName;

    public function __construct(string $url, ?string $userName = null)
    {
        $this->url = $url;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->subject('Valida tu correo electrónico')
            ->view('emails.verify-updated-email');
    }
}
