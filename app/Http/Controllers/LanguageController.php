<?php

namespace App\Http\Controllers;

use App\Services\LanguageService;
use Exception;

class LanguageController extends Controller
{
    /**
     * Bağımlılık Enjeksiyonu (Dependency Injection)
     */
    public function __construct(protected LanguageService $languageService) {}

    /**
     * Dil değiştirme isteğini karşılar
     * DİKKAT: P1132 Hatası için "string $locale" tip belirtimi eklendi.
     */
    public function switch(string $locale)
    {
        try {
            // İş mantığı tamamen Servise devredildi
            $this->languageService->switchLanguage($locale);

            return back();
        } catch (Exception $e) {
            abort(400, $e->getMessage());
        }
    }
}
