<?php

namespace App\Services\AI\Providers;

use App\Contracts\AI\LLMProviderInterface;
use Illuminate\Support\Facades\Http;
use Exception;
use InvalidArgumentException;

class GoogleLLMProvider implements LLMProviderInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $apiKey = config('ai.providers.google.api_key');
        $model = config('ai.providers.google.models.completion');

        if (empty($apiKey)) {
            throw new InvalidArgumentException("Sistem Hatası: Google Gemini API Anahtarı bulunamadı.");
        }

        $this->apiKey = (string) $apiKey;
        $this->model = (string) $model;
    }

    public function askQuestion(string $question, array $context): string
    {
        $verifySsl = !app()->isLocal();
        $contextText = implode("\n\n---\n\n", $context);

        $systemInstruction = "Sen kurumsal bir Sözleşme ve Doküman Yönetim Sistemi asistanısın. " .
            "Sana verilen 'Doküman Bağlamı' (Context) dışındaki bilgilere dayanarak cevap verme. " .
            "Eğer cevap bağlamda yoksa 'Bu bilgiye erişiminiz olan dokümanlarda rastlanmamıştır.' de.";

        $prompt = "Sistem Talimatı:\n" . $systemInstruction . "\n\n" .
            "Doküman Bağlamı:\n" . $contextText . "\n\n" .
            "Kullanıcı Sorusu: " . $question;

        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->apiKey}";
        $response = Http::withOptions(['verify' => $verifySsl])
            ->timeout(config('ai.providers.google.timeout'))
            ->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.2, // Kurumsal ciddiyet için düşük halüsinasyon oranı
                ]
            ]);

        if ($response->failed()) {
            throw new Exception("Google Gemini LLM API hatası: " . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text');
    }
}
