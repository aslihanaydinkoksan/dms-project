<?php

namespace App\Services;

use App\Interfaces\AssistantServiceInterface;
use App\Models\BotIntent;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class RuleBasedAssistantService implements AssistantServiceInterface
{
    public function ask(string $message, User $user): array
    {
        // 1. TEMİZLİK: Türkçe karakterleri küçült, fazla boşlukları ve noktalama işaretlerini sil
        $messageStr = mb_strtolower(trim($message), 'UTF-8');
        $messageStr = preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $messageStr));
        $userWords = array_filter(explode(' ', $messageStr));

        $intents = BotIntent::all();
        $bestIntent = null;
        $maxScore = 0;

        foreach ($intents as $intent) {
            // Veritabanından gelen veriyi kesin olarak Dizi'ye (Array) çevir
            $keywords = $intent->keywords;
            if (is_string($keywords)) {
                $decoded = json_decode($keywords, true);
                $keywords = is_array($decoded) ? $decoded : array_map('trim', explode(',', $keywords));
            }

            if (!is_array($keywords) || empty($keywords)) continue;

            foreach ($keywords as $keyword) {
                $keywordStr = mb_strtolower(trim($keyword), 'UTF-8');
                $keywordStr = preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $keywordStr));

                // SENARYO 1: Kullanıcının mesajı, anahtar kelimeyi doğrudan içeriyorsa (Örn: "yeni belge")
                if (str_contains($messageStr, $keywordStr) || str_contains($keywordStr, $messageStr)) {
                    return $this->prepareResponse($intent);
                }

                // SENARYO 2: Kelime kelime parçala ve eşleşme oranını bul (Örn: "belge yeni")
                $keywordWords = array_filter(explode(' ', $keywordStr));
                if (empty($keywordWords)) continue;

                $matchCount = 0;
                foreach ($keywordWords as $kw) {
                    // Kullanıcının yazdığı kelimeler arasında bu kelime geçiyor mu?
                    foreach ($userWords as $uWord) {
                        if (str_contains($uWord, $kw) || str_contains($kw, $uWord)) {
                            $matchCount++;
                            break; // Eşleşme bulundu, diğer kelimeye geç
                        }
                    }
                }

                // Kaç kelimenin eşleştiğine göre Yüzde (%) Skoru hesapla
                $matchPercentage = ($matchCount / count($keywordWords)) * 100;

                if ($matchPercentage > $maxScore) {
                    $maxScore = $matchPercentage;
                    $bestIntent = $intent;
                }
            }
        }

        // KARAR ANI: Cümlenin en az %50'sini (yarısını) doğru anladıysa cevabı ver!
        if ($bestIntent && $maxScore >= 50) {
            return $this->prepareResponse($bestIntent);
        }

        // HİÇBİR EŞLEŞME YOKSA
        return [
            'reply' => 'Sizi tam olarak anlayamadım. Belge yüklemek, klasörlere gitmek veya profilinizi mi görmek istersiniz?',
            'link' => route('documents.index'),
            'link_text' => 'Tüm Belgelerim'
        ];
    }

    /**
     * Cevabı formatlayıp döndüren yardımcı metod
     */
    private function prepareResponse(BotIntent $intent): array
    {
        $url = ($intent->action_route && Route::has($intent->action_route))
            ? route($intent->action_route)
            : null;

        return [
            'reply' => $intent->response_text,
            'link' => $url,
            'link_text' => $intent->action_button_text ?? 'İlgili Sayfaya Git'
        ];
    }
}
