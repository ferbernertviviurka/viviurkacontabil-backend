<?php

namespace App\Mail;

use App\Models\Boleto;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChargeNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $boleto;
    public $company;

    /**
     * Create a new message instance.
     */
    public function __construct(Boleto $boleto, Company $company)
    {
        $this->boleto = $boleto;
        $this->company = $company;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $tipo = match ($this->boleto->tipo_pagamento) {
            'boleto' => 'Boleto',
            'pix' => 'PIX',
            'credit_card' => 'Cartão de Crédito',
            default => 'Cobrança',
        };

        return new Envelope(
            subject: "Nova Cobrança - {$tipo} - R$ " . number_format($this->boleto->valor, 2, ',', '.'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.charge-notification',
            with: [
                'boleto' => $this->boleto,
                'company' => $this->company,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        // Attach boleto PDF if available
        if ($this->boleto->tipo_pagamento === 'boleto' && $this->boleto->url_pdf) {
            // In production, download and attach the PDF
            // For now, we'll just include the link in the email
        }

        return $attachments;
    }
}
