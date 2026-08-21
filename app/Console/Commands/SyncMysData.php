<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DepartmentSyncService;
use App\Services\UserSyncService;

class SyncMysData extends Command
{
    // Terminalden çalıştırılacak komutun adı
    protected $signature = 'mys:sync';
    protected $description = 'MYS üzerinden departmanları ve kullanıcıları senkronize eder.';

    public function handle(DepartmentSyncService $deptSync, UserSyncService $userSync)
    {
        $this->info('MYS Senkronizasyonu başlatılıyor...');

        try {
            $deptCount = $deptSync->sync();
            $this->info("{$deptCount} departman senkronize edildi.");

            $userCount = $userSync->sync();
            $this->info("{$userCount} kullanıcı senkronize edildi.");

            $this->info('Senkronizasyon başarıyla tamamlandı!');
        } catch (\Exception $e) {
            $this->error('Hata: ' . $e->getMessage());
        }
    }
}
