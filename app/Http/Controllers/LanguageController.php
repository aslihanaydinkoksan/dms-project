<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function switch($locale)
    {
        // Güvenlik: Sadece desteklenen dillere izin ver
        if (!in_array($locale, ['tr', 'en'])) {
            abort(400, 'Unsupported Language');
        }

        // Kullanıcı giriş yapmışsa kalıcı olarak DB'ye kaydet
        if (Auth::check()) {
            Auth::user()->update(['locale' => $locale]);
        }

        // Session'ı güncelle ve anlık dili ayarla
        Session::put('locale', $locale);
        App::setLocale($locale);

        return back();
    }
}
