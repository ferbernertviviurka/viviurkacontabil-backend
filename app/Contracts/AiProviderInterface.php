<?php

namespace App\Contracts;

interface AiProviderInterface
{
    /**
     * Gerar texto com IA.
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function generate(string $prompt, array $options = []): array;

    /**
     * Resumir texto.
     *
     * @param string $text
     * @return array
     */
    public function summarize(string $text): array;

    /**
     * Gerar sugestões.
     *
     * @param string $context
     * @return array
     */
    public function suggest(string $context): array;
}

