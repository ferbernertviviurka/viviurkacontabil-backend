<?php

namespace App\Providers\AiProviders;

use App\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiProvider implements AiProviderInterface
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', '');
        $this->apiUrl = config('services.openai.api_url', 'https://api.openai.com/v1');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * Gerar texto com OpenAI.
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function generate(string $prompt, array $options = []): array
    {
        try {
            Log::info('OpenAI: Gerando resposta', ['prompt' => substr($prompt, 0, 100)]);

            // Mock implementation
            return [
                'success' => true,
                'text' => $this->generateMockResponse($prompt),
                'tokens_used' => rand(50, 150),
                'model' => $this->model,
            ];

            // Em produção:
            // $response = Http::withHeaders([
            //     'Authorization' => 'Bearer ' . $this->apiKey,
            // ])->post($this->apiUrl . '/chat/completions', [
            //     'model' => $this->model,
            //     'messages' => [
            //         ['role' => 'user', 'content' => $prompt],
            //     ],
            //     'max_tokens' => $options['max_tokens'] ?? 500,
            // ]);
            //
            // if ($response->successful()) {
            //     $data = $response->json();
            //     return [
            //         'success' => true,
            //         'text' => $data['choices'][0]['message']['content'],
            //         'tokens_used' => $data['usage']['total_tokens'],
            //         'model' => $this->model,
            //     ];
            // }
        } catch (\Exception $e) {
            Log::error('OpenAI: Erro ao gerar', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Resumir texto.
     *
     * @param string $text
     * @return array
     */
    public function summarize(string $text): array
    {
        $prompt = "Resuma o seguinte texto de forma clara e concisa:\n\n{$text}";
        return $this->generate($prompt);
    }

    /**
     * Gerar sugestões.
     *
     * @param string $context
     * @return array
     */
    public function suggest(string $context): array
    {
        $prompt = "Com base no seguinte contexto, forneça 3 sugestões práticas e úteis:\n\n{$context}";
        return $this->generate($prompt);
    }

    /**
     * Generate mock response for testing.
     *
     * @param string $prompt
     * @return string
     */
    private function generateMockResponse(string $prompt): string
    {
        if (str_contains(strtolower($prompt), 'resumo') || str_contains(strtolower($prompt), 'resuma')) {
            return "Este é um resumo gerado pela IA. O texto foi analisado e os principais pontos foram identificados, proporcionando uma visão geral concisa do conteúdo original.";
        }

        if (str_contains(strtolower($prompt), 'sugestões') || str_contains(strtolower($prompt), 'suggest')) {
            return "1. Organize os documentos por categoria para facilitar o acesso.\n2. Configure notificações automáticas para vencimentos.\n3. Mantenha um backup regular de todos os arquivos importantes.";
        }

        if (str_contains(strtolower($prompt), 'email')) {
            return "Prezado cliente,\n\nEsperamos que esta mensagem o encontre bem. Entramos em contato para informar sobre os próximos vencimentos e solicitar o envio da documentação necessária.\n\nFicamos à disposição para esclarecer quaisquer dúvidas.\n\nAtenciosamente,\nEquipe Viviurka Contábil";
        }

        return "Esta é uma resposta gerada pela IA baseada no seu prompt. Em um ambiente de produção, esta seria uma resposta mais elaborada e contextualizada gerada pelo modelo GPT.";
    }
}

