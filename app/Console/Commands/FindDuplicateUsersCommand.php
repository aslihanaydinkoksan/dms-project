<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class FindDuplicateUsersCommand extends Command
{
    protected $signature = 'dms:find-duplicates';
    protected $description = 'Sistemdeki potansiyel mükerrer (duplicate) kullanıcıları isim benzerliğine göre gruplayıp listeler.';

    public function handle()
    {
        $this->info("Veritabanı taranıyor... Bu işlem birkaç saniye sürebilir.");

        $users = User::withTrashed()->get();

        $groupedByName = $users->groupBy(function ($user) {
            $name = str_replace(['İ', 'I'], ['i', 'ı'], $user->name);
            return trim(mb_strtolower($name, 'UTF-8'));
        })->filter(function ($group) {
            return $group->count() > 1;
        });

        if ($groupedByName->isEmpty()) {
            $this->info("Harika! Sistemde isim benzerliğine sahip mükerrer kullanıcı bulunamadı.");
            return self::SUCCESS;
        }

        $headers = ['ID', 'İsim', 'E-Posta', 'Son Giriş', 'Durum / Çöp Kutusu'];
        $rows = [];

        foreach ($groupedByName as $normalizedName => $duplicateUsers) {
            if (!empty($rows)) {
                $rows[] = ['---', '---', '---', '---', '---'];
            }

            foreach ($duplicateUsers as $u) {
                $status = [];
                if (!$u->is_active) $status[] = 'Pasif';
                if ($u->trashed()) $status[] = 'Silinmiş';
                if (empty($status)) $status[] = 'Aktif';

                $rows[] = [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->last_login_at ? $u->last_login_at->format('d.m.Y H:i') : 'GİRİŞ YAPMADI',
                    implode(', ', $status)
                ];
            }
        }

        $this->line("\nToplam <options=bold,underscore>{$groupedByName->count()}</> adet mükerrer isim grubu bulundu:\n");
        $this->table($headers, $rows);

        $this->info("\nNasıl Birleştirilir?");
        $this->line("Örnek: php artisan dms:merge-users <GİRİŞ_YAPMAYAN_ID> <SON_GİRİŞ_YAPAN_ID>\n");

        return self::SUCCESS;
    }
}
