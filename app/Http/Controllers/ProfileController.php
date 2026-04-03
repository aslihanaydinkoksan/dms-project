<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|min:6|confirmed', // password_confirmation inputu gerektirir
        ]);

        $user->name = $validated['name'];
        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return back()->with('success', 'Profil bilgileriniz güncellendi.');
    }
    public function notificationSettings()
    {
        $prefs = Auth::user()->notification_preferences ?? [];
        return view('profile.notifications', compact('prefs'));
    }

    public function updateNotificationSettings(Request $request)
    {
        $user = Auth::user();

        // Formdan gelen veriyi al, gelmeyenleri (Checkbox işaretlenmemişse) false yap
        $prefs = [
            'physical_assigned' => [
                'mail' => $request->has('physical_assigned_mail'),
                'database' => $request->has('physical_assigned_db'),
            ],
            'workflow_action' => [
                'mail' => $request->has('workflow_action_mail'),
                'database' => $request->has('workflow_action_db'),
            ],
            'document_revision' => [
                'mail' => $request->has('document_revision_mail'),
                'database' => $request->has('document_revision_db'),
            ]
        ];

        $user->notification_preferences = $prefs;
        $user->save();

        return back()->with('success', 'Bildirim tercihleriniz başarıyla güncellendi.');
    }
    public function markAllNotificationsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back();
    }
    /**
     * Kullanıcının tüm bildirim geçmişini listeler
     */
    public function notificationsHistory()
    {
        // auth()->user()->notifications() metodu hem okunmuş hem okunmamış TÜM bildirimleri getirir.
        $notifications = Auth::user()->notifications()->paginate(15);

        return view('profile.notifications-history', compact('notifications'));
    }
    /**
     * Arka plan JS (Polling) için okunmamış bildirim sayısını döner.
     */
    public function checkUnreadNotifications()
    {
        if (!Auth::check()) {
            return response()->json(['count' => 0]);
        }

        // Sadece okunmamış bildirimlerin sayısını alıyoruz
        $count = Auth::user()->unreadNotifications()->count();

        return response()->json(['count' => $count]);
    }
    /**
     * Kullanıcının Çok Gizli belgeler için kullanacağı Özel Kasa Şifresini günceller.
     */
    public function updateVaultPassword(Request $request)
    {
        // 1. Validasyon (Kurallar)
        $request->validate([
            // Şifre zorunlu, en az 6 karakter olmalı ve 'vault_password_confirmation' alanıyla eşleşmeli (confirmed)
            'vault_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            // Kullanıcı dostu Türkçe hata mesajları
            'vault_password.required' => 'Lütfen yeni bir kasa şifresi girin.',
            'vault_password.min' => 'Kasa şifreniz güvenlik sebebiyle en az 6 karakter olmalıdır.',
            'vault_password.confirmed' => 'Girdiğiniz şifreler birbiriyle eşleşmiyor. Lütfen kontrol edin.',
        ]);

        // 2. Şifreyi Hash'le (Kriptola) ve Veritabanına Kaydet
        $request->user()->update([
            'vault_password' => Hash::make($request->vault_password)
        ]);

        // 3. Kullanıcıyı başarı mesajıyla geri döndür
        return back()->with('success', '🔐 Kasa şifreniz başarıyla oluşturuldu/güncellendi. Artık "Çok Gizli" belgelere bu şifreyle erişebilirsiniz.');
    }
    /**
     * Kullanıcı Kasa Şifresini unutursa, Ana Sistem Şifresi ile sıfırlamasını sağlar.
     */
    public function resetVaultPassword(Request $request)
    {
        // 1. Önce Güvenlik: Gerçekten hesap sahibi mi işlemi yapıyor?
        $request->validate([
            // Laravel bu kural ile formdan gelen şifrenin, oturum açmış kullanıcının
            // ANA sistem şifresiyle eşleşip eşleşmediğini otomatik kontrol eder!
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.required' => 'Sıfırlama işlemi için mevcut sistem şifrenizi girmelisiniz.',
            'current_password.current_password' => 'Girdiğiniz ana sistem şifresi hatalı. İşlem reddedildi.',
        ]);

        // 2. Kasa şifresini veritabanından sil (NULL yap)
        $request->user()->update([
            'vault_password' => null
        ]);

        // 3. Kullanıcıyı bilgilendir
        return back()->with('success', '🗑️ Kasa şifreniz başarıyla sıfırlandı. Artık çok gizli belgelere standart sistem şifrenizle erişebilirsiniz. Dilerseniz yeni bir kasa şifresi belirleyebilirsiniz.');
    }
}
