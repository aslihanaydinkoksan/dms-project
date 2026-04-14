<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = config('app.locale'); // Varsayılan sistem dili ('tr')

        if (Auth::check()) {
            // Kullanıcı giriş yaptıysa veritabanındaki dili önceliklendir
            $locale = Auth::user()->locale ?? Session::get('locale', $locale);
        } else {
            // Ziyaretçi ise Session'a bak
            $locale = Session::get('locale', $locale);
        }

        App::setLocale($locale);
        Carbon::setLocale($locale);
        Session::put('locale', $locale); // Session'ı her ihtimale karşı senkronize tut

        return $next($request);
    }
}
