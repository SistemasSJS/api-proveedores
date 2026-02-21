<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombre;
    public $email;
    public $telefono;
    public $empresa;
    public $mensaje;

    public function __construct($nombre, $email, $telefono, $empresa, $mensaje)
    {
        $this->nombre = $nombre;
        $this->email = $email;
        $this->telefono = $telefono;
        $this->empresa = $empresa;
        $this->mensaje = $mensaje;
    }

    public function build()
    {
        return $this->subject('Nuevo mensaje de contacto - ' . $this->nombre)
            ->replyTo($this->email, $this->nombre)
            ->view('emails.contacto');
    }
}
