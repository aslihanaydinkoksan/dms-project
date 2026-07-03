<?php

namespace App\Contracts\AI;

interface EmbeddingProviderInterface
{
    /**
     * Verilen metni (chunk) matematiksel bir vektör dizisine çevirir.
     * Sistemi belirli bir modelin embedding yapısına bağımlı olmaktan kurtarır. 
     * Gönderilen metni alıp her modelin kendi boyutuna (örn: 1536) uygun bir vektör (sayı dizisi) döneceğini garanti eder.
     *
     * @param string $text Vektörleştirilecek metin
     * @return array<float> Vektör dizisi (örn: [0.001, -0.023, ...])
     */
    public function embedText(string $text): array;
}