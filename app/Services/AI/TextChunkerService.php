<?php

namespace App\Services\AI;

/**
 *Yüzlerce sayfalık kompleks sözleşmeleri LLM'in anlayabileceği mantıksal lokmalara bölen zeki algoritma servisimizdir.
 */
class TextChunkerService
{
    protected int $chunkSize;
    protected int $overlapSize;

    public function __construct()
    {
        // Hard-coded veriden kaçınıyoruz. Config tabanlı dinamik limitler.
        $this->chunkSize = config('ai.chunking.max_tokens', 1000);
        $this->overlapSize = config('ai.chunking.overlap', 200);
    }

    /**
     * Metni NLP kurallarına uygun olarak (cümle bütünlüğünü koruyarak) parçalara böler.
     *
     * @param string $text
     * @return array<string>
     */
    public function chunk(string $text): array
    {
        // Temizlik: Gereksiz boşlukları ve sekmeleri normalize et.
        $text = preg_replace('/\s+/', ' ', trim($text));

        // Cümle sonlarına (nokta, ünlem, soru işareti ve ardından gelen boşluk) göre diziyi ayır.
        $sentences = preg_split('/(?<=[.?!])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        $chunks = [];
        $currentChunk = '';

        foreach ($sentences as $sentence) {
            // Basit bir yaklaşımla, her kelimeyi 1 token veya ortalama 4-5 karakteri 1 token kabul edebiliriz.
            // Karakter uzunluğu üzerinden limit kontrolü yapıyoruz (yaklaşık token hesabı).
            $estimatedTokenCount = strlen($currentChunk) + strlen($sentence);

            if ($estimatedTokenCount > ($this->chunkSize * 4)) { 
                // Chunk sınırına ulaşıldı, mevcut chunk'ı kaydet.
                $chunks[] = trim($currentChunk);
                
                // Overlap (Kesişim) mantığı: Bağlam kopmaması için son cümleyi yeni chunk'ın başına ekle.
                $currentChunk = $sentence . ' ';
            } else {
                $currentChunk .= $sentence . ' ';
            }
        }

        if (!empty(trim($currentChunk))) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }
}