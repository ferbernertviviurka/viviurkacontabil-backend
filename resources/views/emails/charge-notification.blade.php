<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nova Cobrança - {{ $boleto->tipo_pagamento === 'boleto' ? 'Boleto' : ($boleto->tipo_pagamento === 'pix' ? 'PIX' : 'Cartão de Crédito') }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">Nova Cobrança Criada</h1>
        
        <p>Olá, <strong>{{ $company->responsavel_financeiro_nome ?? $company->razao_social }}</strong>!</p>
        
        <p>Uma nova cobrança foi criada para a empresa <strong>{{ $company->razao_social }}</strong>.</p>
        
        <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h2 style="margin-top: 0;">Detalhes da Cobrança</h2>
            <p><strong>Valor:</strong> R$ {{ number_format($boleto->valor, 2, ',', '.') }}</p>
            <p><strong>Vencimento:</strong> {{ \Carbon\Carbon::parse($boleto->vencimento)->format('d/m/Y') }}</p>
            @if($boleto->descricao)
                <p><strong>Descrição:</strong> {{ $boleto->descricao }}</p>
            @endif
            <p><strong>Método de Pagamento:</strong> 
                @if($boleto->tipo_pagamento === 'boleto')
                    Boleto Bancário
                @elseif($boleto->tipo_pagamento === 'pix')
                    PIX
                @else
                    Cartão de Crédito
                @endif
            </p>
        </div>

        @if($boleto->tipo_pagamento === 'pix')
            <div style="background: #dbeafe; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #1e40af;">Pagamento via PIX</h3>
                
                @if($boleto->chave_pix)
                    <p><strong>Chave PIX (Copia e Cola):</strong></p>
                    <div style="background: white; padding: 10px; border-radius: 4px; border: 1px solid #ddd; margin: 10px 0;">
                        <code style="font-size: 14px; word-break: break-all; display: block;">{{ $boleto->chave_pix }}</code>
                    </div>
                    <p style="font-size: 12px; color: #6b7280;">Copie o código acima e cole no aplicativo do seu banco para realizar o pagamento via PIX.</p>
                @endif

                @if($boleto->qr_code_pix)
                    <p style="margin-top: 20px;"><strong>QR Code PIX:</strong></p>
                    <p>Escaneie o QR Code abaixo com o aplicativo do seu banco:</p>
                    <img src="data:image/png;base64,{{ $boleto->qr_code_pix }}" alt="QR Code PIX" style="max-width: 300px; display: block; margin: 10px auto; border: 2px solid #ddd; border-radius: 8px; padding: 10px; background: white;">
                @endif
            </div>
        @elseif($boleto->tipo_pagamento === 'boleto')
            <div style="background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #92400e;">Pagamento via Boleto Bancário</h3>
                
                @if($boleto->linha_digitavel)
                    <p><strong>Linha Digitável:</strong></p>
                    <div style="background: white; padding: 10px; border-radius: 4px; border: 1px solid #ddd; margin: 10px 0;">
                        <code style="font-size: 16px; font-weight: bold; letter-spacing: 2px; display: block; text-align: center;">{{ $boleto->linha_digitavel }}</code>
                    </div>
                @endif

                @if($boleto->codigo_barras)
                    <p><strong>Código de Barras:</strong></p>
                    <div style="background: white; padding: 10px; border-radius: 4px; border: 1px solid #ddd; margin: 10px 0;">
                        <code style="font-size: 14px; word-break: break-all; display: block;">{{ $boleto->codigo_barras }}</code>
                    </div>
                @endif

                @if($boleto->url_pdf)
                    <p style="margin-top: 20px;">
                        <a href="{{ $boleto->url_pdf }}" style="background: #f59e0b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Baixar Boleto em PDF</a>
                    </p>
                    <p style="font-size: 12px; color: #6b7280; margin-top: 10px;">Clique no botão acima para baixar o boleto em PDF e realizar o pagamento em qualquer banco ou lotérica.</p>
                @endif
            </div>
        @elseif($boleto->tipo_pagamento === 'credit_card')
            <div style="background: #dcfce7; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h3 style="color: #166534;">Pagamento via Cartão de Crédito</h3>
                <p>Clique no link abaixo para realizar o pagamento com cartão de crédito:</p>
                @if($boleto->link_pagamento)
                    <p style="margin-top: 20px;">
                        <a href="{{ $boleto->link_pagamento }}" style="background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">Pagar com Cartão de Crédito</a>
                    </p>
                    <p style="font-size: 12px; color: #6b7280; margin-top: 10px;">Você será redirecionado para uma página segura onde poderá inserir os dados do seu cartão de crédito.</p>
                @else
                    <p style="color: #dc2626;">Link de pagamento não disponível. Entre em contato conosco.</p>
                @endif
            </div>
        @endif

        <div style="background: #fee2e2; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc2626;">
            <p style="margin: 0; color: #991b1b; font-weight: bold;">⚠️ Atenção</p>
            <p style="margin: 5px 0 0 0; color: #991b1b; font-size: 14px;">
                Esta cobrança vence em <strong>{{ \Carbon\Carbon::parse($boleto->vencimento)->format('d/m/Y') }}</strong>. 
                Realize o pagamento até a data de vencimento para evitar atrasos.
            </p>
        </div>

        <p style="margin-top: 30px; color: #6b7280; font-size: 14px;">
            Em caso de dúvidas sobre esta cobrança, entre em contato conosco através do email ou telefone cadastrado.
        </p>
        
        <p style="margin-top: 20px;">
            Atenciosamente,<br>
            <strong>Viviurka Contábil</strong>
        </p>
    </div>
</body>
</html>
