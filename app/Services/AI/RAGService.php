<?php

namespace App\Services\AI;

use App\Contracts\AI\EmbeddingProviderInterface;
use App\Contracts\AI\LLMProviderInterface;

/**
 * Uygulamamızın (Controller'lar veya Job'lar) muhatap olacağı tek merkezdir (Facade / Service Pattern). 
 * Yapay zeka sağlayıcısının kim olduğunu bilmez, sadece Interface'leri tanır.
 */
class RAGService
{
    // Dependency Injection (Bağımlılık Enjeksiyonu) ile interfaceleri alıyoruz.
    public function __construct(
        protected EmbeddingProviderInterface $embeddingProvider,
        protected LLMProviderInterface $llmProvider
    ) {}

    /**
     * Gelen metni vektörleştirir. (Job'lar tarafından kullanılacak)
     */
    public function generateEmbedding(string $text): array
    {
        return $this->embeddingProvider->embedText($text);
    }

    /**
     * Kullanıcı sorusunu cevaplar. (Controller tarafından kullanılacak)
     */
    public function answerQuery(string $question, array $documentContexts): string
    {
        // İlerleyen adımlarda; soru buraya gelmeden önce vektör veritabanında (Qdrant/MySQL)
        // arama yapılacak ve yetki kontrolünden geçmiş $documentContexts dizisi buraya beslenecek.
        return $this->llmProvider->askQuestion($question, $documentContexts);
    }
}