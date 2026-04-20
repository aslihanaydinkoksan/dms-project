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
        // Mesajı küçük harfe çevir ve temizle
        $messageStr = Str::lower($message);

        // Şimdilik performansı etkilemez ama ileride Cache'lenebilir
        $intents = BotIntent::all();

        foreach ($intents as $intent) {
            foreach ($intent->keywords as $keyword) {
                // Eğer kullanıcının cümlesinde anahtar kelime geçiyorsa (Smart Match)
                if (Str::contains($messageStr, Str::lower($keyword))) {

                    // Rota adı veritabanında var mı ve gerçekten sistemde tanımlı mı?
                    $url = ($intent->action_route && Route::has($intent->action_route))
                        ? route($intent->action_route)
                        : null;

                    return [
                        'reply' => $intent->response_text,
                        'link' => $url,
                        'link_text' => $intent->action_button_text ?? 'Buraya Tıklayın'
                    ];
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
}
