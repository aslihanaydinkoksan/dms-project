<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Services\LanguageService;

class SetLocale
{
    /**
     * Bağımlılık Enjeksiyonu (Dependency Injection)
     */
    public function __construct(protected LanguageService $languageService) {}

    /**
     * Gelen her isteği yakalar ve dili ayarlar.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Verileri topla (Karar verme, sadece topla)
        $userLocale = Auth::check() ? Auth::user()->locale : null;
        $sessionLocale = Session::get('locale');

        // 2. İşi Servise (Uzmana) devret
        $this->languageService->resolveAndApplyLocale($userLocale, $sessionLocale);

        return $next($request);
    }
}