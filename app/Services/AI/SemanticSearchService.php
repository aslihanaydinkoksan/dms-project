<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\DocumentEmbedding;
use Exception;

/**
 * SemanticSearchService, kullanıcı sorularına en yakın anlamı taşıyan doküman parçalarını (chunk) bulmak için kullanılır.
 * Bu servis, RAGService üzerinden vektörleştirme (embedding) işlemi yapar ve ardından veritabanındaki doküman embeddingleri ile karşılaştırır.
 */
class SemanticSearchService
{
    public function __construct(
        protected RAGService $ragService
    ) {}

    /**
     * Soruya en yakın anlamı taşıyan doküman parçalarını (chunk) bulur.
     *
     * @param string $question Kullanıcı sorusu
     * @param int $documentId Aranacak dokümanın ID'si
     * @param User $user Yetki kontrolü (RBAC) için kullanıcı objesi
     * @return array<string> En alakalı metin parçaları
     */
    public function search(string $question, int $documentId, User $user): array
    {
        // 1. Soruyu vektörleştir
        $questionVector = $this->ragService->generateEmbedding($question);
        
        $limit = config('ai.search_limit', 5);

        // 2. İlgili dokümanın vektörlerini veritabanından çek
        // (Burada ilerleyen aşamalarda $user üzerinden ekstra departman/gizlilik filtreleri Where clause olarak eklenebilir)
        $embeddings = DocumentEmbedding::where('document_id', $documentId)
            ->select('chunk_text', 'vector_data')
            ->get();

        if ($embeddings->isEmpty()) {
            return [];
        }

        $scoredChunks = [];

        // 3. PHP üzerinde Kosinüs Benzerliği hesapla (Sadece o dokümana ait parçalar olduğu için son derece hızlıdır)
        foreach ($embeddings as $embedding) {
            $chunkVector = $embedding->vector_data;
            
            // Eğer veri JSON değilse veya boşsa atla
            if (!is_array($chunkVector)) {
                continue;
            }

            $score = $this->calculateCosineSimilarity($questionVector, $chunkVector);
            
            $scoredChunks[] = [
                'score' => $score,
                'text'  => $embedding->chunk_text
            ];
        }

        // 4. Skorlara göre büyükten küçüğe sırala (En alakalı olanlar en üstte)
        usort($scoredChunks, fn($a, $b) => $b['score'] <=> $a['score']);

        // 5. Sadece en iyi N adet parçanın metnini array olarak döndür
        return array_map(
            fn($item) => $item['text'], 
            array_slice($scoredChunks, 0, $limit)
        );
    }

    /**
     * İki vektör arasındaki yönsel benzerliği hesaplar. (+1 mükemmel uyum, -1 tam zıtlık)
     */
    protected function calculateCosineSimilarity(array $vectorA, array $vectorB): float
    {
        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        $count = min(count($vectorA), count($vectorB));

        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $magnitudeA += $vectorA[$i] ** 2;
            $magnitudeB += $vectorB[$i] ** 2;
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
}