<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lembrete de Pagamento - Mensalidade</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">Lembrete de Pagamento</h1>
        
        <p>Olá, <strong>{{ $company->razao_social }}</strong>!</p>
        
        <p>Este é um lembrete de que sua mensalidade referente ao mês <strong>{{ $payment->mes_referencia }}</strong> está próxima do vencimento.</p>
        
        <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h2 style="margin-top: 0;">Detalhes da Mensalidade</h2>
            <p><strong>Plano:</strong> {{ $subscription->plano }}</p>
            <p><strong>Valor:</strong> R$ {{ number_format($payment->valor, 2, ',', '.') }}</p>
            <p><strong>Vencimento:</strong> {{ \Carbon\Carbon::parse($payment->data_vencimento)->format('d/m/Y') }}</p>
        </div>

        @if(isset($paymentData['metodo_pagamento']) && $paymentData['metodo_pagamento'] === 'pix')
            <div style="background: #dbeafe; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #1e40af;">Pagamento via PIX</h3>
                <p><strong>Chave PIX:</strong> <code style="background: white; padding: 5px; border-radius: 4px;">{{ $payment->chave_pix }}</code></p>
                @if($payment->qr_code_pix)
                    <p>Escaneie o QR Code abaixo:</p>
                    <img src="data:image/png;base64,{{ $payment->qr_code_pix }}" alt="QR Code PIX" style="max-width: 300px; display: block; margin: 10px 0;">
                @endif
            </div>
        @elseif(isset($paymentData['metodo_pagamento']) && $paymentData['metodo_pagamento'] === 'boleto')
            <div style="background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #92400e;">Pagamento via Boleto</h3>
                <p>O boleto está anexo a este email.</p>
                @if(isset($paymentData['boleto_url']))
                    <p><a href="{{ $paymentData['boleto_url'] }}" style="background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Baixar Boleto</a></p>
                @endif
            </div>
        @elseif(isset($paymentData['metodo_pagamento']) && $paymentData['metodo_pagamento'] === 'credit_card')
            <div style="background: #dcfce7; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #166534;">Pagamento via Cartão de Crédito</h3>
                <p>Clique no link abaixo para realizar o pagamento:</p>
                @if(isset($paymentData['link_pagamento']))
                    <p><a href="{{ $paymentData['link_pagamento'] }}" style="background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Pagar com Cartão</a></p>
                @endif
            </div>
        @endif

        <p style="margin-top: 30px; color: #6b7280; font-size: 14px;">
            Em caso de dúvidas, entre em contato conosco.
        </p>
        
        <p style="margin-top: 20px;">
            Atenciosamente,<br>
            <strong>Viviurka Contábil</strong>
        </p>
    </div>
</body>
</html>

