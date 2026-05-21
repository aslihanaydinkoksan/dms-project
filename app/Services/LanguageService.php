<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\SystemSetting;
use Exception;

class LanguageService
{
    /**
     * Kullanıcının ve sistemin dilini değiştirir (Controller'dan tetiklenir)
     */
    public function switchLanguage(string $locale): void
    {
        $supportedLocales = $this->getSupportedLocales();

        if (!in_array($locale, $supportedLocales)) {
            throw new Exception('Desteklenmeyen dil seçeneği.');
        }

        if (Auth::check()) {
            Auth::user()->update(['locale' => $locale]);
        }

        $this->applyLocale($locale);
    }

    /**
     * Her HTTP isteğinde Middleware tarafından tetiklenir.
     * Güvenli ve doğrulanmış dili sisteme uygular.
     */
    public function resolveAndApplyLocale(?string $userLocale, ?string $sessionLocale): void
    {
        $supportedLocales = $this->getSupportedLocales();
        $defaultLocale = $this->getDefaultLocale();

        // Öncelik Sırası: User DB > Session > System Default
        $locale = $userLocale ?? $sessionLocale ?? $defaultLocale;

        // Validasyon: Seçilen dil sistemde aktif mi? Değilse varsayılana dön.
        if (!in_array($locale, $supportedLocales)) {
            $locale = $defaultLocale;
        }

        $this->applyLocale($locale);
    }

    /**
     * Dili Laravel'e ve Carbon'a (Tarih kütüphanesi) entegre eder
     */
    private function applyLocale(string $locale): void
    {
        App::setLocale($locale);
        Carbon::setLocale($locale);
        Session::put('locale', $locale);
    }

    /**
     * CACHE PATTERN: Desteklenen dilleri RAM'de tutar (DB yükünü sıfırlar)
     */
    public function getSupportedLocales(): array
    {
        return Cache::rememberForever('system_supported_locales', function () {
            return SystemSetting::getByKey('supported_locales', ['tr', 'en']);
        });
    }

    /**
     * CACHE PATTERN: Sistem varsayılan dilini RAM'de tutar
     */
    public function getDefaultLocale(): string
    {
        return Cache::rememberForever('system_default_locale', function () {
            return SystemSetting::getByKey('default_locale', config('app.locale'));
        });
    }
}