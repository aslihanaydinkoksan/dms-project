<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Document;
use App\Models\User;
use App\Services\VaultService;

class EnsureSensitiveAccess
{
    /**
     * Bağımlılık Enjeksiyonu (Dependency Injection)
     */
    public function __construct(protected VaultService $vaultService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $routeParam = $request->route('document');

        $document = $routeParam instanceof Document
            ? $routeParam
            : Document::find($routeParam);

        if (!$document) {
            return $next($request);
        }

        // IDE'ye bu değişkenin kesinlikle bir Document olduğunu söylüyoruz
        assert($document instanceof Document);

        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Bu gizli evraka erişmek için sistemde oturum açmalısınız.');
        }

        // IDE'nin $user->can() metodunda şüpheye düşmemesi için
        assert($user instanceof User);

        // --- 1. ADIM - YETKİ (AUTHORIZATION) KONTROLÜ ---
        if (!$user->can('view', $document)) {
            return redirect()->route('dashboard')
                ->with('error', '🛑 Güvenlik İhlali: Bu gizli evrakı görüntüleme yetkiniz bulunmuyor. Erişiminiz engellendi.');
        }

        // --- 2. ADIM - DOĞRULAMA (ZERO TRUST / SUDO) KONTROLÜ ---
        if ($document->requires_vault && !$this->vaultService->isVaultUnlocked()) {

            // ÇÖZÜM: Global session() yerine $request üzerinden session çağrıyoruz.
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()->route('documents.vault', $document->id)
                ->with('warning', 'Bu belge yüksek gizlilik seviyesine sahiptir. Devam etmek için kimliğinizi (Kasa Şifrenizi) doğrulayın.');
        }

        return $next($request);
    }
}
