<?php

namespace App\Services\AI\Providers;

use App\Contracts\AI\EmbeddingProviderInterface;
use Illuminate\Support\Facades\Http;
use Exception;
use InvalidArgumentException;

/**
 * Sadece metinleri vektörleştirmekten sorumlu OpenAI implementasyonudur. API anahtarlarını ve model isimlerini  config üzerinden alır.
 */
class OpenAIEmbeddingProvider implements EmbeddingProviderInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $apiKey = config('ai.providers.openai.api_key');
        $model = config('ai.providers.openai.models.embedding');

        // Savunmacı Programlama: Konfigürasyon eksikse anlamlı bir hata fırlat
        if (empty($apiKey)) {
            throw new InvalidArgumentException("Sistem Hatası: OpenAI API Anahtarı bulunamadı. Lütfen .env dosyasında OPENAI_API_KEY değerini tanımlayın.");
        }

        $this->apiKey = (string) $apiKey;
        $this->model = (string) $model;
    }

    public function embedText(string $text): array
    {
        // Yerel geliştirme ortamında SSL hatasını aşmak için verify: false ekliyoruz
        $verifySsl = !app()->isLocal();

        $response = Http::withToken($this->apiKey)
            ->timeout(config('ai.providers.openai.timeout'))
            ->withOptions(['verify' => $verifySsl]) // SSL doğrulamasını local'de esnet
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => $text,
            ]);

        if ($response->failed()) {
            throw new Exception("OpenAI API hatası: " . $response->body());
        }

        return $response->json('data.0.embedding');
    }
}
