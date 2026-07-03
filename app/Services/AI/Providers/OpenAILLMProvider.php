<?php

namespace App\Services\AI\Providers;

use App\Contracts\AI\LLMProviderInterface;
use Illuminate\Support\Facades\Http;
use Exception;
use InvalidArgumentException;

/**
 * Sadece soru cevaplamadan (Completion/Chat) sorumlu OpenAI implementasyonudur. Prompt yapısı burada şekillenir ancak kurgu dinamiktir.
 */
class OpenAILLMProvider implements LLMProviderInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $apiKey = config('ai.providers.openai.api_key');
        $model = config('ai.providers.openai.models.completion');

        // Savunmacı Programlama: Konfigürasyon eksikse anlamlı bir hata fırlat
        if (empty($apiKey)) {
            throw new InvalidArgumentException("Sistem Hatası: OpenAI API Anahtarı bulunamadı. Lütfen .env dosyasında OPENAI_API_KEY değerini tanımlayın.");
        }

        $this->apiKey = (string) $apiKey;
        $this->model = (string) $model;
    }

    public function askQuestion(string $question, array $context): string
    {
        // Bağlam (Context) metinlerini birleştiriyoruz.
        $contextText = implode("\n\n---\n\n", $context);

        // Sisteme kurumsal kimliğini hatırlatan ve sadece verilen bağlamla cevap üretmesini söyleyen sistem mesajı.
        $systemMessage = "Sen kurumsal bir Sözleşme ve Doküman Yönetim Sistemi asistanısın. " .
            "Sana verilen 'Doküman Bağlamı' (Context) dışındaki bilgilere dayanarak cevap verme. " .
            "Eğer cevap bağlamda yoksa 'Bu bilgiye erişiminiz olan dokümanlarda rastlanmamıştır.' de.";

        $response = Http::withToken($this->apiKey)
            ->timeout(config('ai.providers.openai.timeout'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => "Doküman Bağlamı:\n" . $contextText . "\n\nSoru: " . $question],
                ],
                'temperature' => 0.2, // Kurumsal cevaplar için düşük halüsinasyon riski (deterministik).
            ]);

        if ($response->failed()) {
            throw new Exception("OpenAI LLM API hatası: " . $response->body());
        }

        return $response->json('choices.0.message.content');
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
