<?php

namespace App\Services;

use App\Interfaces\AssistantServiceInterface;
use App\Models\BotIntent;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class RuleBasedAssistantService implements AssistantServiceInterface
{
    public function ask(string $message, User $user): array
    {
        // 1. Geliştirme: Türkçe karakterleri bozmadan küçük harfe çevir
        $messageStr = mb_strtolower(trim($message), 'UTF-8');

        $intents = BotIntent::all();

        foreach ($intents as $intent) {
            foreach ($intent->keywords as $keyword) {
                $keywordStr = mb_strtolower(trim($keyword), 'UTF-8');

                // Senaryo A: Birebir yan yana geçiyorsa (Örn: "yeni belge")
                if (Str::contains($messageStr, $keywordStr)) {
                    return $this->prepareResponse($intent);
                }

                // Senaryo B (SİHİRLİ DOKUNUŞ): Kelimelerin sırası farklıysa
                // Anahtar kelimeyi parçala (["yeni", "belge"]) ve hepsi mesajda var mı bak
                $keywordWords = array_filter(explode(' ', $keywordStr));
                $allWordsMatch = true;

                foreach ($keywordWords as $word) {
                    if (!Str::contains($messageStr, $word)) {
                        $allWordsMatch = false;
                        break;
                    }
                }

                // Eğer anahtar kelimedeki TÜM kelimeler mesajda dağınık halde varsa eşleş!
                if (count($keywordWords) > 0 && $allWordsMatch) {
                    return $this->prepareResponse($intent);
                }
            }
        }

        // EŞLEŞME BULUNAMAZSA (Fallback)
        return [
            'reply' => 'Bunu tam anlayamadım, ancak dilerseniz erişim yetkiniz olan tüm belgeleri listeleyebilirim.',
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
