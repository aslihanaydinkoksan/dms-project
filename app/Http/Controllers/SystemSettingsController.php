<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    public function notificationSettings()
    {
        // Modelindeki description alanını da doldurarak ayarı oluştur/getir
        $settings = SystemSetting::firstOrCreate(
            ['key' => 'global_notifications'],
            [
                'value' => [
                    'mail_enabled' => true,
                    'frequency' => 'instant',
                    'cc_managers' => false,
                ],
                'description' => 'Sistem genelindeki asenkron e-posta ve zil bildirimi şalterleri.'
            ]
        );

        return view('settings.notifications', ['config' => $settings->value]);
    }

    public function updateNotificationSettings(Request $request)
    {
        $setting = SystemSetting::where('key', 'global_notifications')->first();

        $setting->update([
            'value' => [
                'mail_enabled' => $request->has('mail_enabled'),
                'frequency' => $request->input('frequency', 'instant'),
                'cc_managers' => $request->has('cc_managers'),
            ]
        ]);

        return back()->with('success', 'Global bildirim kuralları başarıyla güncellendi.');
    }
}
