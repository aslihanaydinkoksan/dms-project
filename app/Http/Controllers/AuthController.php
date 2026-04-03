<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // 1. ZIRH: Temel Form Doğrulaması (KVKK ve reCAPTCHA boş geçilemez)
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'kvkk' => 'accepted', // "accepted", checkbox'ın 'on', 'yes', '1' veya 'true' olmasını zorunlu kılar.
            'g-recaptcha-response' => 'required'
        ], [
            'kvkk.accepted' => 'Sisteme giriş yapabilmek için KVKK Aydınlatma Metni\'ni onaylamanız gerekmektedir.',
            'g-recaptcha-response.required' => 'Lütfen robot olmadığınızı (reCAPTCHA) doğrulayın.',
            'email.required' => 'E-posta adresi zorunludur.',
            'password.required' => 'Şifre zorunludur.'
        ]);

        // 2. ZIRH: Google reCAPTCHA API Doğrulaması (Arka Plan)
        // Kullanıcı arayüzde tiki işaretlese bile, arka planda Google'a "Bu token gerçek mi?" diye soruyoruz.
        $recaptchaResponse = Http::withoutVerifying()->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $request->input('g-recaptcha-response'),
            'remoteip' => $request->ip(),
        ]);

        if (!$recaptchaResponse->json('success')) {
            // Eğer Google "Bu bir bot" derse veya süre dolmuşsa, hatayı fırlatıp login ekranına geri atıyoruz
            throw ValidationException::withMessages([
                'g-recaptcha-response' => ['Güvenlik doğrulaması (reCAPTCHA) başarısız oldu veya zaman aşımına uğradı. Lütfen sayfayı yenileyip tekrar deneyin.'],
            ]);
        }

        // 3. ASIL GİRİŞ İŞLEMİ
        // attempt() fonksiyonuna sadece email ve şifreyi veriyoruz. (KVKK ve Recaptcha DB'ye gitmesin diye)
        $attemptData = $request->only('email', 'password');

        if (Auth::attempt($attemptData)) {
            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        // Şifre yanlışsa
        return back()->withErrors(['email' => 'Verilen bilgiler sistemdekilerle eşleşmiyor.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
