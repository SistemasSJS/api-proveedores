<?php

namespace App\Mail;

use App\Models\Presupuesto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PresupuestoEnviadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Presupuesto $presupuesto,
        public string $enlacePublico,
        public string $nombreReceptor
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Presupuesto ' . $this->presupuesto->numero_presupuesto . ' - ' . ($this->presupuesto->proveedor?->nombre_comercial ?? $this->presupuesto->proveedor?->razon_social ?? 'Proveedor'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.presupuesto.enviado',
        );
    }

    public function attachments(): array
    {
        // En envío por correo de presupuesto solo se comparte enlace público.
        return [];
    }
}
