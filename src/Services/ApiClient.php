<?php

namespace Danilocorrente\ModulosCorrenteam\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class ApiClient
{
    protected string $baseUrl;
    protected ?string $token;
    protected bool $hasLaravel;

    public function __construct()
    {
        $this->hasLaravel = function_exists('config');

        if ($this->hasLaravel) {
            $isLocal = filter_var(config('modulos_correnteam.local'), FILTER_VALIDATE_BOOLEAN);
            $this->token = config('modulos_correnteam.token');
        } else {
            // Fallback para rodar fora do Laravel
            $dotenv = __DIR__ . '/../../../.env';
            $isLocal = false;
            $this->token = null;

            if (file_exists($dotenv)) {
                $lines = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (str_starts_with($line, 'CORRENTEAM_API_TOKEN=')) {
                        $this->token = trim(explode('=', $line, 2)[1]);
                    }
                    if (str_starts_with($line, 'CORRENTEAM_LOCAL=')) {
                        $isLocal = trim(explode('=', $line, 2)[1]) === 'true';
                    }
                }
            }
        }

        $this->baseUrl = $isLocal
            ? 'http://correnteam.test/api'
            : 'https://correnteam.com.br/api';

        // ✅ Garantir que o Guzzle existe
        if (!$this->hasLaravel && !class_exists('\GuzzleHttp\Client')) {
            throw new RuntimeException("Guzzle não encontrado. Rode: composer require guzzlehttp/guzzle");
        }
    }

    /**
     * Faz upload da CNH para o endpoint OCR.
     */
    public function ocrCnh($file): array
    {
        return $this->postFile('ocr/cnh', $file);
    }

    /**
     * Faz upload de arquivo multipart/form-data.
     */
    protected function postFile(string $endpoint, $file, string $fieldName = 'file'): array
    {
        if ($file instanceof UploadedFile) {
            $path = $file->getRealPath();
            $filename = $file->getClientOriginalName();
        } else {
            $path = (string) $file;
            if (!file_exists($path)) {
                throw new RuntimeException("Arquivo não encontrado: {$path}");
            }
            $filename = basename($path);
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        // Laravel presente → usa Facade
        if ($this->hasLaravel) {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/json',
                ])
                ->attach($fieldName, fopen($path, 'r'), $filename)
                ->post($url)
                ->throw()
                ->json();

            return $response;
        } else {
            

        // Fora do Laravel → usa Guzzle
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => $this->token,
                'Accept' => 'application/json',
            ],
            'http_errors' => false,
            'timeout' => 60,
        ]);

        $response = $client->post($url, [
            'multipart' => [
                [
                    'name'     => $fieldName,
                    'contents' => fopen($path, 'r'),
                    'filename' => $filename,
                ],
            ],
        ]);
        }

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Erro ao decodificar resposta: {$body}");
        }

        return $decoded;
    }
}
