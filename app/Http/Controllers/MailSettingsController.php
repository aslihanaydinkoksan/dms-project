<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting; 
use Illuminate\Support\Facades\Auth;

class MailSettingsController extends Controller
{
    /**
     * Mail ayarları sayfasını gösterir
     */
    public function index()
    {
        // Yetki kontrolü (Controller bazlı ekstra güvenlik)
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
        }

        // Tüm ayarları veritabanından çekip key => value dizisine çeviriyoruz
        $settings = SystemSetting::pluck('value', 'key')->toArray();

        return view('settings.mail', compact('settings'));
    }

    /**
     * Formdan gelen ayarları veritabanına kaydeder
     */
    public function update(Request $request)
    {
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Yetkisiz işlem.');
        }

        // Token dışındaki tüm form verilerini al
        $data = $request->except(['_token']);

        // Her bir anahtar-değer (key-value) ikilisini güncelle veya yoksa oluştur
        foreach ($data as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Mail şablonları ve uyarı ayarları başarıyla güncellendi.');
    }
}
