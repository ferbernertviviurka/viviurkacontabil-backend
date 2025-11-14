<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MonthlyPaymentReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $payment;
    public $company;
    public $subscription;
    public $paymentData;

    /**
     * Create a new message instance.
     */
    public function __construct($payment, $company, $subscription, $paymentData)
    {
        $this->payment = $payment;
        $this->company = $company;
        $this->subscription = $subscription;
        $this->paymentData = $paymentData;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = "Lembrete de Pagamento - Mensalidade {$this->payment->mes_referencia}";

        $mail = $this->subject($subject)
            ->view('emails.monthly-payment-reminder')
            ->with([
                'payment' => $this->payment,
                'company' => $this->company,
                'subscription' => $this->subscription,
                'paymentData' => $this->paymentData,
            ]);

        // Attach boleto PDF if available
        if (isset($this->paymentData['boleto_url']) && $this->paymentData['boleto_url']) {
            // In production, download and attach the PDF
            // $mail->attach($this->paymentData['boleto_url']);
        }

        return $mail;
    }
}

