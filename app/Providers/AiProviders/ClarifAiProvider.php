<?php

namespace App\Providers\AiProviders;

use App\Contracts\AiProviderInterface;
use App\Models\Setting;
use Clarifai\API\ClarifaiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClarifAiProvider implements AiProviderInterface
{
    protected string $apiKey;
    protected string $userId;
    protected string $appId;
    protected string $modelId;
    protected ?string $modelVersionId;

    public function __construct()
    {
        // Get settings from database
        $this->apiKey = Setting::get('ai.clarifai.api_key', '') ?: Setting::get('clarifai_api_key', '');
        
        // user_id and app_id - required for app-scoped models like gpt-oss-120b
        // Default to 'openai' and 'chat-completion' for OpenAI models via ClarifAI
        $userId = Setting::get('ai.clarifai.user_id', 'openai');
        if (empty($userId) || $userId === 'clarifai') {
            $userId = 'openai';
            // Auto-fix incorrect value in database
            Setting::set('ai.clarifai.user_id', 'openai', 'string', 'ai', 'User ID do ClarifAI');
        }
        $this->userId = $userId;
        
        $appId = Setting::get('ai.clarifai.app_id', 'chat-completion');
        if (empty($appId)) {
            $appId = 'chat-completion';
            Setting::set('ai.clarifai.app_id', 'chat-completion', 'string', 'ai', 'App ID do ClarifAI');
        }
        $this->appId = $appId;
        
        $this->modelId = Setting::get('ai.clarifai.model', 'gpt-oss-120b') ?: Setting::get('clarifai_model', 'gpt-oss-120b');
        $this->modelVersionId = Setting::get('ai.clarifai.model_version_id', '1c1365f924224107a9cd72b0a9e633a6') ?: Setting::get('clarifai_model_version_id', '1c1365f924224107a9cd72b0a9e633a6');
    }

    /**
     * Generate text with ClarifAI.
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function generate(string $prompt, array $options = []): array
    {
        try {
            if (empty($this->apiKey)) {
                throw new \Exception('ClarifAI API Key não configurada');
            }

            // Force correct values - ensure user_id is always 'openai' for OpenAI models
            $userId = $this->userId;
            if ($userId === 'clarifai' || empty($userId)) {
                $userId = 'openai';
                $this->userId = 'openai';
                // Update in database
                Setting::set('ai.clarifai.user_id', 'openai', 'string', 'ai', 'User ID do ClarifAI');
                Log::warning('ClarifAI: user_id corrigido para openai');
            }
            
            $appId = $this->appId;
            if (empty($appId)) {
                $appId = 'chat-completion';
                $this->appId = 'chat-completion';
                Setting::set('ai.clarifai.app_id', 'chat-completion', 'string', 'ai', 'App ID do ClarifAI');
            }

            Log::info('ClarifAI: Gerando resposta', [
                'model' => $this->modelId,
                'user_id' => $userId,
                'app_id' => $appId,
                'model_version_id' => $this->modelVersionId,
                'prompt_length' => strlen($prompt),
            ]);

            // Build the API URL
            // Use the model endpoint with version ID in URL
            $url = "https://api.clarifai.com/v2/models/{$this->modelId}/outputs";
            
            // Add version ID to URL if specified
            if ($this->modelVersionId) {
                $url = "https://api.clarifai.com/v2/models/{$this->modelId}/versions/{$this->modelVersionId}/outputs";
            }

            // Prepare request body with user_app_id
            // This is REQUIRED for app-scoped models like gpt-oss-120b from OpenAI
            $requestBody = [
                'user_app_id' => [
                    'user_id' => $userId,
                    'app_id' => $appId,
                ],
                'inputs' => [
                    [
                        'data' => [
                            'text' => [
                                'raw' => $prompt,
                            ],
                        ],
                    ],
                ],
            ];
            
            Log::debug('ClarifAI: Request preparado', [
                'url' => $url,
                'user_id' => $userId,
                'app_id' => $appId,
                'model' => $this->modelId,
                'model_version_id' => $this->modelVersionId,
            ]);

            // Make HTTP request
            $response = Http::withHeaders([
                'Authorization' => 'Key ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, $requestBody);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('ClarifAI: Erro na resposta HTTP', [
                    'status' => $response->status(),
                    'url' => $url,
                    'request_body' => $requestBody,
                    'response_body' => $errorBody,
                ]);
                throw new \Exception("ClarifAI API Error: " . $errorBody);
            }

            $responseData = $response->json();

            // Check status
            if (!isset($responseData['status']['code']) || $responseData['status']['code'] !== 10000) {
                $errorMsg = $responseData['status']['description'] ?? 'Unknown error';
                $errorDetails = $responseData['status']['details'] ?? '';
                $errorCode = $responseData['status']['code'] ?? 'Unknown';
                
                Log::error('ClarifAI: Erro na API', [
                    'code' => $errorCode,
                    'description' => $errorMsg,
                    'details' => $errorDetails,
                ]);
                
                // Provide helpful error message
                if ($errorCode === 11101) {
                    $errorMsg = "Recurso não encontrado. Verifique se o user_id, app_id e model_id estão corretos. " . 
                                "Para modelos do OpenAI via ClarifAI, use user_id='openai' e app_id='chat-completion'. " .
                                "Erro detalhado: {$errorDetails}";
                } elseif ($errorCode === 11102) {
                    $errorMsg = "Requisição inválida. Certifique-se de que user_id e app_id estão preenchidos corretamente no user_app_id do body da requisição.";
                }
                
                throw new \Exception("ClarifAI API Error ({$errorCode}): {$errorMsg}" . ($errorDetails ? " - {$errorDetails}" : ""));
            }

            // Extract response text
            $responseText = '';
            if (isset($responseData['outputs'][0]['data']['text']['raw'])) {
                $responseText = $responseData['outputs'][0]['data']['text']['raw'];
            } elseif (isset($responseData['outputs'][0]['data']['text'])) {
                $responseText = is_string($responseData['outputs'][0]['data']['text']) 
                    ? $responseData['outputs'][0]['data']['text']
                    : json_encode($responseData['outputs'][0]['data']['text']);
            }

            // Estimate tokens (approximation: 1 token ≈ 4 characters)
            $tokensUsed = (int) ceil(strlen($prompt . $responseText) / 4);

            Log::info('ClarifAI: Resposta gerada com sucesso', [
                'tokens_used' => $tokensUsed,
                'response_length' => strlen($responseText),
            ]);

            return [
                'success' => true,
                'text' => $responseText,
                'tokens_used' => $tokensUsed,
                'model' => $this->modelId,
                'provider' => 'clarifai',
            ];
        } catch (\Exception $e) {
            Log::error('ClarifAI: Erro ao gerar resposta', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => 'clarifai',
            ];
        }
    }

    /**
     * Summarize text.
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
     * Generate suggestions.
     *
     * @param string $context
     * @return array
     */
    public function suggest(string $context): array
    {
        $prompt = "Com base no seguinte contexto, forneça 3 sugestões práticas e úteis:\n\n{$context}";
        return $this->generate($prompt);
    }
}

