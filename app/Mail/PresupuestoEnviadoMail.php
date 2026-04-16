<?php

namespace App\Mail;

use App\Models\Presupuesto;
use App\Support\PresupuestoPdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PresupuestoEnviadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Presupuesto $presupuesto,
        public string $enlacePublico,
        public string $nombreReceptor,
        public bool $incluirInvitacion = false
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
        $filename = 'Presupuesto_' . ($this->presupuesto->numero_presupuesto ?: $this->presupuesto->id) . '.pdf';

        return [
            Attachment::fromData(
                fn () => PresupuestoPdf::renderPdfBinary($this->presupuesto),
                $filename
            )->withMime('application/pdf'),
        ];
    }
}
