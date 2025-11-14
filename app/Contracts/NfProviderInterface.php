<?php

namespace App\Contracts;

interface NfProviderInterface
{
    /**
     * Emitir uma nota fiscal.
     *
     * @param array $dados
     * @return array
     */
    public function emitir(array $dados): array;

    /**
     * Consultar status de uma nota fiscal.
     *
     * @param string $id
     * @return array
     */
    public function consultar(string $id): array;

    /**
     * Cancelar uma nota fiscal.
     *
     * @param string $id
     * @return array
     */
    public function cancelar(string $id): array;
}

