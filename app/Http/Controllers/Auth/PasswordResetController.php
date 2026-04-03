<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    // 1. E-posta İstek Formunu Göster
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    // 2. Sıfırlama Linkini Gönder
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['success' => 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi!'])
            : back()->withErrors(['email' => 'Bu e-posta adresiyle eşleşen bir hesap bulunamadı.']);
    }

    // 3. Yeni Şifre Belirleme Formunu Göster
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    // 4. Şifreyi Güncelle
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ], [
            'password.confirmed' => 'Girdiğiniz şifreler birbiriyle eşleşmiyor.',
            'password.min' => 'Şifreniz en az 6 karakter olmalıdır.'
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Şifreniz başarıyla sıfırlandı. Yeni şifrenizle giriş yapabilirsiniz.')
            : back()->withErrors(['email' => 'Şifre sıfırlama başarısız oldu. Linkin süresi dolmuş veya geçersiz olabilir.']);
    }
}
