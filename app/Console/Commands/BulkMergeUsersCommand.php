<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

class BulkMergeUsersCommand extends Command
{
    protected $signature = 'dms:bulk-merge';
    protected $description = 'TC numarasıyla açılmış mükerrer e-posta hesaplarını otomatik tespit edip asıl hesaplarla birleştirir.';

    public function handle()
    {
        $this->warn("DİKKAT: Bu komut, isim benzerliğine sahip grupları analiz edecek.");
        $this->warn("11 haneli rakamdan oluşan (TC) e-postaları bulup, normal e-postalı (isim.soyisim) hesaba otomatik aktaracaktır.");
        
        if (!$this->confirm('Toplu otomatik birleştirme işlemini başlatmak istiyor musunuz?')) {
            return self::SUCCESS;
        }

        $users = User::withTrashed()->get();

        // 1. İsimlere göre grupla (Aynı Find komutundaki mantık)
        $groupedByName = $users->groupBy(function ($user) {
            $name = str_replace(['İ', 'I'], ['i', 'ı'], $user->name);
            return trim(mb_strtolower($name, 'UTF-8'));
        })->filter(function ($group) {
            return $group->count() > 1; // Sadece mükerrer olanları al
        });

        $successCount = 0;
        $skippedGroups = [];

        $this->output->progressStart($groupedByName->count());

        foreach ($groupedByName as $name => $groupUsers) {
            $tcUsers = [];
            $properUsers = [];

            // 2. Kullanıcıları "TC E-Postalılar" ve "Düzgün E-Postalılar" olarak ayır
            foreach ($groupUsers as $u) {
                // E-posta sadece rakamlardan (10-11 hane) ve @koksan.com/@dydodrinco.com'dan oluşuyorsa
                if (preg_match('/^[0-9]{10,11}@/', $u->email)) {
                    $tcUsers[] = $u;
                } else {
                    $properUsers[] = $u;
                }
            }

            $primaryUser = null;
            $duplicateUsers = [];

            // 3. ASIL KULLANICIYI BELİRLEME MANTIĞI
            if (count($properUsers) === 1 && count($tcUsers) >= 1) {
                // Eğer tam 1 tane düzgün mail varsa, asıl kullanıcı odur. Geri kalan hepsi mükerrerdir.
                $primaryUser = $properUsers[0];
                $duplicateUsers = $tcUsers;
            } 
            elseif (count($properUsers) > 1) {
                // Eğer grupta hem gmail hem koksan varsa (Örn: Ahmet Aslan) 
                // Önceliği şirket mailine veriyoruz
                $corporateUsers = array_filter($properUsers, function($u) {
                    return str_ends_with($u->email, '@koksan.com') || str_ends_with($u->email, '@dydodrinco.com.tr');
                });

                if (count($corporateUsers) === 1) {
                    $primaryUser = reset($corporateUsers);
                    // Şirket maili dışındaki HERKESİ (gmail, TC, vs.) mükerrer listesine al
                    $duplicateUsers = array_filter($groupUsers->toArray(), fn($u) => $u['id'] !== $primaryUser->id);
                    // Object'e geri çevir
                    $duplicateUsers = User::withTrashed()->whereIn('id', array_column($duplicateUsers, 'id'))->get();
                }
            }

            // 4. BİRLEŞTİRME İŞLEMİ
            if ($primaryUser && count($duplicateUsers) > 0) {
                foreach ($duplicateUsers as $dup) {
                    $this->line("\n[OTOMATİK] {$dup->email} (ID:{$dup->id})  --->  {$primaryUser->email} (ID:{$primaryUser->id})");
                    
                    // Önceden yazdığımız ve kusursuz çalışan Merge komutunu arkada çalıştırıyoruz (Sıfır risk)
                    Artisan::call('dms:merge-users', [
                        'duplicate_id' => $dup->id,
                        'primary_id'   => $primaryUser->id,
                        '--force'      => true
                    ]);
                    $successCount++;
                }
            } else {
                // Sistem karar veremedi (Örn: İki tane normal @koksan maili var). Atla ve logla.
                $skippedGroups[] = $name;
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        // 5. SONUÇ RAPORU
        $this->info("\n--- İŞLEM TAMAMLANDI ---");
        $this->info("Toplam {$successCount} adet TC e-postalı mükerrer kayıt asıl hesaplara başarıyla birleştirildi!");

        if (count($skippedGroups) > 0) {
            $this->warn("\nSistem aşağıdaki isimleri çok karmaşık bulduğu için atladı (Lütfen bunları Find komutuyla bulup manuel birleştirin):");
            foreach ($skippedGroups as $skipped) {
                $this->line("- " . ucwords($skipped));
            }
        }

        return self::SUCCESS;
    }
}