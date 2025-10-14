<?php

namespace Danilocorrente\ModulosCorrenteam\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BuscaCEP
{
    /**
     * Busca informações de endereço via CEP com fallback automático.
     *
     * @param  string  $cep
     * @return array
     */
    public function buscar(string $cep): array
    {
        $cep = preg_replace('/\D/', '', $cep);

        try {
            if (strlen($cep) !== 8) {
                throw new RuntimeException("CEP inválido: {$cep}");
            }

            // Tenta primeiro com ViaCEP
            $response = Http::timeout(8)->get("https://viacep.com.br/ws/{$cep}/json/");

            if ($response->failed()) {
                throw new RuntimeException("Falha na conexão com ViaCEP (HTTP {$response->status()})");
            }

            $data = $response->json();

            // Se ViaCEP retornar erro, faz fallback
            if (isset($data['erro']) && $data['erro'] === true) {
                Log::warning("[BuscaCEP] ViaCEP não encontrou o CEP {$cep}. Tentando fallback BrasilAPI...");
                $data = $this->buscarFallbackBrasilApi($cep);
            }

            return $this->formatarRetorno($data);

        } catch (\Throwable $e) {
            Log::error("[BuscaCEP] Erro ao buscar CEP {$cep}: {$e->getMessage()}", [
                'cep' => $cep,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->retornoErro($e->getMessage());
        }
    }

    /**
     * Fallback: busca na BrasilAPI.
     */
    private function buscarFallbackBrasilApi(string $cep): array
    {
        try {
            $response = Http::timeout(8)->get("https://brasilapi.com.br/api/cep/v1/{$cep}");

            if ($response->failed()) {
                throw new RuntimeException("Fallback BrasilAPI falhou (HTTP {$response->status()})");
            }

            return $response->json();
        } catch (\Throwable $e) {
            throw new RuntimeException("Erro ao buscar CEP na BrasilAPI: {$e->getMessage()}");
        }
    }

    /**
     * Formata retorno unificado entre ViaCEP e BrasilAPI.
     */
    private function formatarRetorno(array $data): array
    {
        return [
            'cep'         => $data['cep'] ?? null,
            'logradouro'  => $data['logradouro'] ?? $data['street'] ?? null,
            'bairro'      => $data['bairro'] ?? $data['neighborhood'] ?? null,
            'localidade'  => $data['localidade'] ?? $data['city'] ?? null,
            'uf'          => $data['uf'] ?? $data['state'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'ddd'         => $data['ddd'] ?? null,
            'ibge'        => $data['ibge'] ?? null,
            'source'      => isset($data['street']) ? 'brasilapi' : 'viacep',
            'error'       => null,
        ];
    }

    /**
     * Retorno padrão em caso de erro.
     */
    private function retornoErro(string $mensagem): array
    {
        return [
            'cep'         => null,
            'logradouro'  => null,
            'bairro'      => null,
            'localidade'  => null,
            'uf'          => null,
            'complemento' => null,
            'ddd'         => null,
            'ibge'        => null,
            'source'      => null,
            'error'       => $mensagem,
        ];
    }
}
