<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Carbon;

class EnsureSensitiveAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Rota parametresinden belgeyi al (Obje veya ID olabilir)
        $document = $request->route('document');
        if (! $document instanceof \App\Models\Document) {
            $document = \App\Models\Document::find($document);
        }

        // Belge yoksa yola devam et, 404'e düşsün
        if (!$document) {
            return $next($request);
        }

        // --- YENİ: 1. ADIM - ÖNCE YETKİ (AUTHORIZATION) KONTROLÜ ---
        // Kullanıcı şifreyi (Kasa şifresini) bilse bile bu belgeyi görme hakkı var mı?
        // Bu sayede URL'yi tahmin edip aradan sızmaya çalışanları şifre ekranına bile sokmadan kapıdan çeviriyoruz.
        if (! $request->user()->can('view', $document)) {
            return redirect()->route('dashboard')
                ->with('error', '🛑 Güvenlik İhlali: Bu gizli evrakı görüntüleme yetkiniz bulunmuyor. Erişiminiz engellendi.');
        }

        // --- 2. ADIM - SONRA DOĞRULAMA (AUTHENTICATION / SUDO) KONTROLÜ ---
        // Gizlilik Seviyesi Kontrolü (Hizmete Özel veya Çok Gizli ise Kasa devreye girer)
        if (in_array($document->privacy_level, ['confidential', 'strictly_confidential'])) {
            $unlockedUntil = session('vault_unlocked_until');

            // Kasada süre yoksa veya süre dolmuşsa
            if (! $unlockedUntil || \Carbon\Carbon::parse($unlockedUntil)->isPast()) {

                // Kullanıcının gitmek istediği asıl URL'i (Örn: İndirme veya Görüntüleme linkini) hafızaya al
                session()->put('url.intended', $request->fullUrl());

                return redirect()->route('documents.vault', $document->id)
                    ->with('warning', 'Bu belge yüksek gizlilik seviyesine sahiptir. Devam etmek için kimliğinizi (Kasa Şifrenizi) doğrulayın.');
            }
        }

        // Hem yasal yetkisi var, hem de şifresini girmiş (veya belge gizli değil). Kapıyı aç!
        return $next($request);
    }
}
