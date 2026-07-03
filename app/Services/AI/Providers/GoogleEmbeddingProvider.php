<?php

namespace App\Services\AI\Providers;

use App\Contracts\AI\EmbeddingProviderInterface;
use Illuminate\Support\Facades\Http;
use Exception;
use InvalidArgumentException;

class GoogleEmbeddingProvider implements EmbeddingProviderInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $apiKey = config('ai.providers.google.api_key');
        $model = config('ai.providers.google.models.embedding');

        if (empty($apiKey)) {
            throw new InvalidArgumentException("Sistem Hatası: Google Gemini API Anahtarı bulunamadı. Lütfen .env dosyasında GOOGLE_GEMINI_API_KEY değerini tanımlayın.");
        }

        $this->apiKey = (string) $apiKey;
        $this->model = (string) $model;
    }

    public function embedText(string $text): array
    {
        $verifySsl = !app()->isLocal(); // Local ortamda SSL hatasını önlemek için

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:embedContent?key={$this->apiKey}";

        $response = Http::withOptions(['verify' => $verifySsl])
            ->timeout(config('ai.providers.google.timeout'))
            ->post($url, [
                'model' => 'models/' . $this->model,
                'content' => [
                    'parts' => [
                        ['text' => $text]
                    ]
                ],
            ]);

        if ($response->failed()) {
            throw new Exception("Google Gemini Embedding API hatası: " . $response->body());
        }

        // Google, vektörü embedding.values dizisi içinde döndürür.
        return $response->json('embedding.values');
    }
}