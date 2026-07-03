<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\AI\EmbeddingProviderInterface;
use App\Contracts\AI\LLMProviderInterface;
use App\Services\AI\Providers\OpenAIEmbeddingProvider;
use App\Services\AI\Providers\OpenAILLMProvider;
use App\Services\AI\Providers\GoogleEmbeddingProvider;
use App\Services\AI\Providers\GoogleLLMProvider;
use InvalidArgumentException;
/**
 * Yapay Zeka Asistanı için gerekli servis sağlayıcılarını (Embedding ve LLM) bind eder.
 * Bu sayede sistem, hangi yapay zeka sağlayıcısını kullanacağını config üzerinden belirleyebilir.
 */
class AIAssistantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Embedding Sağlayıcısını Bind Ediyoruz
        $this->app->singleton(EmbeddingProviderInterface::class, function ($app) {
            $provider = config('ai.default.embedding');
            
            return match ($provider) {
                'openai' => new OpenAIEmbeddingProvider(),
                'google' => new GoogleEmbeddingProvider(),
                // 'azure' => new AzureEmbeddingProvider(),
                // 'ollama' => new LocalOllamaEmbeddingProvider(),
                default => throw new InvalidArgumentException("Desteklenmeyen Embedding Sağlayıcısı: {$provider}"),
            };
        });

        // LLM (Sohbet) Sağlayıcısını Bind Ediyoruz
        $this->app->singleton(LLMProviderInterface::class, function ($app) {
            $provider = config('ai.default.llm');
            
            return match ($provider) {
                'openai' => new OpenAILLMProvider(),
                'google' => new GoogleLLMProvider(),
                // 'claude' => new ClaudeLLMProvider(),
                default => throw new InvalidArgumentException("Desteklenmeyen LLM Sağlayıcısı: {$provider}"),
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}