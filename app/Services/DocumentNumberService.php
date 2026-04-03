<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Document;

class DocumentNumberService
{
    /**
     * Seçilen klasörün (veya üst klasörlerinin) önekine göre sıradaki doküman numarasını üretir.
     */
    public function generateNextNumber(int $folderId): string
    {
        $folder = Folder::findOrFail($folderId);

        // 1. Zeki Fonksiyonu çağır ve bu klasörün gerçek önekini bul
        $prefix = $this->resolvePrefix($folder);

        // 2. Bu öneke sahip en son eklenen belgeyi bul
        // (Sıralamayı id'ye göre değil, doğrudan numaranın kendisine göre yapmak daha garantilidir)
        $latestDocument = Document::where('document_number', 'like', $prefix . '-%')
            ->orderByRaw("CAST(SUBSTRING_INDEX(document_number, '-', -1) AS UNSIGNED) DESC")
            ->first();

        // 3. Eğer hiç belge yoksa 001'den başla
        if (!$latestDocument) {
            return $prefix . '-001';
        }

        // 4. TL-IG-068 formatından sondaki sayıyı kopar ve 1 artır
        $parts = explode('-', $latestDocument->document_number);
        $lastNumber = (int) end($parts);
        $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT); // 001, 002 formatı

        return $prefix . '-' . $nextNumber;
    }

    /**
     * ÖZEL FONKSİYON: Ağaçtaki tüm önekleri (Ana Klasör > Alt Klasör) birleştirir (Örn: IK-TL)
     */
    private function resolvePrefix(Folder $folder): string
    {
        $prefixes = [];
        $current = $folder;

        // Klasörden en tepeye (Root) kadar tırman
        while ($current) {
            if (!empty($current->prefix)) {
                // array_unshift ile başa ekliyoruz ki sıralama (Root -> Child) olsun
                array_unshift($prefixes, $current->prefix);
            }
            $current = $current->parent;
        }

        // Eğer ağaçta hiçbir önek yoksa GENEL döndür, varsa birleştir (Örn: IK-TL)
        return empty($prefixes) ? 'GENEL' : implode('-', $prefixes);
    }
}
