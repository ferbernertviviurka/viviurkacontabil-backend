<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\MonthlyPaymentReminder;
use App\Mail\ChargeNotification;
use App\Models\Boleto;
use App\Models\Company;

class NotificationService
{
    /**
     * Send email notification.
     *
     * @param string $to
     * @param array $data
     * @return bool
     */
    public function sendEmail(string $to, array $data): bool
    {
        try {
            Log::info('Sending email', ['to' => $to, 'data' => $data]);

            // If it's a monthly payment reminder, use the specific mailable
            if (isset($data['payment']) && isset($data['company']) && isset($data['subscription'])) {
                Mail::to($to)->send(new MonthlyPaymentReminder(
                    $data['payment'],
                    $data['company'],
                    $data['subscription'],
                    $data['payment_data'] ?? []
                ));
            } elseif (isset($data['boleto']) && isset($data['company'])) {
                // Charge notification (Boleto, PIX, or Credit Card)
                $boleto = $data['boleto'] instanceof Boleto 
                    ? $data['boleto'] 
                    : Boleto::find($data['boleto']);
                $company = $data['company'] instanceof Company 
                    ? $data['company'] 
                    : Company::find($data['company']);
                
                if ($boleto && $company) {
                    Mail::to($to)->send(new ChargeNotification($boleto, $company));
                }
            } else {
                // Generic email (log for now - em produção, criar mailable genérico)
                Log::info('Generic email notification', ['to' => $to, 'data' => $data]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending email', [
                'error' => $e->getMessage(),
                'to' => $to,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Send WhatsApp notification.
     *
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function sendWhatsApp(string $phone, string $message): bool
    {
        try {
            Log::info('Sending WhatsApp', ['phone' => $phone, 'message' => $message]);

            // Mock implementation - em produção, integrar com API de WhatsApp
            // $apiUrl = config('services.whatsapp.api_url');
            // $apiKey = config('services.whatsapp.api_key');
            //
            // Http::withHeaders([
            //     'Authorization' => 'Bearer ' . $apiKey,
            // ])->post($apiUrl . '/messages', [
            //     'phone' => $phone,
            //     'message' => $message,
            // ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp', [
                'error' => $e->getMessage(),
                'phone' => $phone,
            ]);

            return false;
        }
    }

    /**
     * Send boleto notification.
     *
     * @param string $method
     * @param array $contact
     * @param array $boletoData
     * @return bool
     */
    public function sendBoletoNotification(string $method, array $contact, array $boletoData): bool
    {
        $message = $this->buildBoletoMessage($boletoData);

        return match ($method) {
            'email' => $this->sendEmail($contact['email'], [
                'message' => $message,
                'boleto_url' => $boletoData['url_pdf'] ?? '#',
            ]),
            'whatsapp' => $this->sendWhatsApp($contact['phone'], $message),
            default => false,
        };
    }

    /**
     * Build boleto message.
     *
     * @param array $boletoData
     * @return string
     */
    private function buildBoletoMessage(array $boletoData): string
    {
        $valor = number_format($boletoData['valor'], 2, ',', '.');
        $vencimento = date('d/m/Y', strtotime($boletoData['vencimento']));

        return "Olá! Segue o boleto para pagamento:\n\n" .
               "Valor: R$ {$valor}\n" .
               "Vencimento: {$vencimento}\n" .
               "Linha Digitável: {$boletoData['linha_digitavel']}\n\n" .
               "Link do boleto: {$boletoData['url_pdf']}";
    }
}

