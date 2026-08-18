<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaBienestarMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $aprendices,
        public string $radicado,
        public ?string $fichaId = null,
        public ?string $programaNombre = null,
        public ?string $fecha = null
    ) {
        $this->fecha = $fecha ?: now()->format('d/m/Y H:i A');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚨 [Alerta Temprana SENA] Remisión de Aprendices en Riesgo Académico — Radicado {$this->radicado}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alerta-bienestar'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
