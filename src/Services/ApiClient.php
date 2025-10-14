<?php

namespace Danilocorrente\ModulosCorrenteam\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApiClient
{
    protected string $baseUrl;
    protected ?string $token;

    public function __construct()
    {
        // Se tiver CORRENTEAM_LOCAL=true → usa o endpoint local
        $isLocal = filter_var(config('modulos_correnteam.local'), FILTER_VALIDATE_BOOLEAN);

        $this->baseUrl = $isLocal
            ? 'http://correnteam.test/api'
            : 'https://correnteam.com.br/api';

        $this->token = config('modulos_correnteam.token');
    }

    protected function url(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Envia arquivo pro endpoint OCR CNH.
     * 
     * @param string|UploadedFile $file
     * @return array
     */
    public function ocrCnh($file): array
    {
        return $this->postFile('ocr/cnh', $file);
    }

    protected function postFile(string $endpoint, $file, string $fieldName = 'file'): array
    {
        if ($file instanceof UploadedFile) {
            $path = $file->getRealPath();
            $filename = $file->getClientOriginalName();
            $mime = $file->getClientMimeType();
        } else {
            $path = (string) $file;
            if (!file_exists($path)) {
                throw new RuntimeException("Arquivo não encontrado: {$path}");
            }
            $filename = basename($path);
            $mime = mime_content_type($path) ?: 'application/octet-stream';
        }

        $response = Http::withHeaders([
                'Authorization' => $this->token,
                'Accept' => 'application/json',
            ])
            ->attach($fieldName, fopen($path, 'r'), $filename)
            ->post($this->url($endpoint))
            ->throw();

        return $response->json();
    }
}
