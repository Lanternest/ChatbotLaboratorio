<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnalisisResumen extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $resumen,
        public readonly string $analisisNombre
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Preparación para tu análisis: {$this->analisisNombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.analisis_resumen',
        );
    }
}