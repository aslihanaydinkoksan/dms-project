<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Support\Facades\Gate;

class ProfileController extends Controller
{
    /**
     * Bağımlılık Enjeksiyonu
     */
    public function __construct(protected ProfileService $profileService) {}

    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'vault_password' => ['required', 'string', 'min:6', 'confirmed'],
            'name' => 'required|string|max:255',
            'password' => 'nullable|min:6|confirmed',
        ]);

        $user = $request->user();
        $user->name = $validated['name'];

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return back()->with('success', 'Profil bilgileriniz güncellendi.');
    }

    public function notificationSettings(Request $request)
    {
        $prefs = $request->user()->notification_preferences ?? [];
        return view('profile.notifications', compact('prefs'));
    }

    public function updateNotificationSettings(Request $request)
    {
        // İş mantığı servise devredildi
        $this->profileService->updateNotificationPreferences($request->user(), $request->all());

        return back()->with('success', 'Bildirim tercihleriniz başarıyla güncellendi.');
    }

    public function markAllNotificationsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return back();
    }

    public function notificationsHistory(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(15);
        return view('profile.notifications-history', compact('notifications'));
    }

    public function clearAllNotifications(Request $request)
    {
        $request->user()->notifications()->delete();
        return back()->with('success', '🧹 Tüm bildirim geçmişiniz başarıyla temizlendi.');
    }

    /**
     * Tek bir bildirimi siler
     * DİKKAT: Laravel bildirim ID'leri string (UUID) tipindedir!
     */
    public function deleteNotification(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return back()->with('success', '🗑️ Bildirim silindi.');
    }

    public function checkUnreadNotifications(Request $request)
    {
        
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return response()->json(['count' => 0]);
        }

        return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
    }

    public function updateVaultPassword(Request $request)
    {
        $request->validate([
            'vault_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'vault_password.required' => 'Lütfen yeni bir kasa şifresi girin.',
            'vault_password.min' => 'Kasa şifreniz güvenlik sebebiyle en az 6 karakter olmalıdır.',
            'vault_password.confirmed' => 'Girdiğiniz şifreler birbiriyle eşleşmiyor. Lütfen kontrol edin.',
        ]);

        $request->user()->update([
            'vault_password' => Hash::make($request->input('vault_password'))
        ]);

        return back()->with('success', '🔐 Kasa şifreniz başarıyla oluşturuldu/güncellendi.');
    }

    public function resetVaultPassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.required' => 'Sıfırlama işlemi için mevcut sistem şifrenizi girmelisiniz.',
            'current_password.current_password' => 'Girdiğiniz ana sistem şifresi hatalı. İşlem reddedildi.',
        ]);

        $request->user()->update(['vault_password' => null]);

        return back()->with('success', '🗑️ Kasa şifreniz başarıyla sıfırlandı.');
    }

    /**
     * Kişisel Verimlilik ve Performans Profilini Gösterir
     * DİKKAT: Kullanıcı ID'si bir integer'dır, gelmeyebilir (?int).
     */
    public function show(Request $request, ?int $id = null)
    {
        $targetUser = $id ? User::findOrFail($id) : $request->user();

        // ZIRH: Başkasının profilini görüntülüyorsa yetkisi var mı?
        if ($id && $id !== $request->user()->id && !$request->user()->hasAnyRole(['Super Admin', 'Admin', 'Direktör', 'Müdür'])) {
            abort(403, 'Bu personelin performans profilini görüntüleme yetkiniz yok.');
        }

        // İşi Servise (Uzmana) devret
        $stats = $this->profileService->getUserPerformanceStats($targetUser);

        return view('profile.show', $stats);
    }

    /**
     * Tek bir bildirimi okundu olarak işaretler ve hedefe yönlendirir.
     * DİKKAT: Laravel bildirim ID'leri string (UUID) tipindedir!
     */
    public function readAndRedirect(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('dashboard');

        return redirect($url);
    }
}
