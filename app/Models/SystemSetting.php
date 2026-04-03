<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    // value kolonunu veritabanından çekerken otomatik olarak PHP Array'ine çevirir.
    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Yardımcı Metot: Ayarı anahtarına göre hızlıca çekmek için (Cache destekli yapılabilir)
     */
    public static function getByKey(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
