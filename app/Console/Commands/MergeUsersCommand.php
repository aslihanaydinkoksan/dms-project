<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class MergeUsersCommand extends Command
{
    /**
     * Komutun terminaldeki adı ve parametreleri
     */
    protected $signature = 'dms:merge-users 
                            {duplicate_id : Mükerrer olan ve silinecek (eski/hatalı) kullanıcının IDsi} 
                            {primary_id : Asıl olan ve verilerin aktarılacağı (kalacak) kullanıcının IDsi}
                            {--force : Onay sormadan işlemi direkt yapar}';

    /**
     * Komutun açıklaması
     */
    protected $description = 'Mükerrer kullanıcının tüm verilerini asıl kullanıcıya aktarır ve mükerrer hesabı güvenle siler.';

    public function handle()
    {
        $duplicateId = $this->argument('duplicate_id');
        $primaryId = $this->argument('primary_id');

        if ($duplicateId == $primaryId) {
            $this->error("HATA: Mükerrer ID ile Asıl ID aynı olamaz!");
            return self::FAILURE;
        }

        // Kullanıcıları bul (Çöp kutusundakiler dahil)
        $duplicateUser = User::withTrashed()->find($duplicateId);
        $primaryUser = User::withTrashed()->find($primaryId);

        if (!$duplicateUser) {
            $this->error("HATA: Mükerrer kullanıcı (ID: {$duplicateId}) bulunamadı!");
            return self::FAILURE;
        }

        if (!$primaryUser) {
            $this->error("HATA: Asıl kullanıcı (ID: {$primaryId}) bulunamadı!");
            return self::FAILURE;
        }

        // Onay al
        if (!$this->option('force')) {
            $this->warn("DİKKAT: '{$duplicateUser->name}' (ID: {$duplicateId}) kullanıcısının TÜM VERİLERİ");
            $this->warn("'{$primaryUser->name}' (ID: {$primaryId}) kullanıcısına aktarılacak.");
            $this->warn("İşlem sonunda '{$duplicateUser->name}' silinecek!");

            if (!$this->confirm('Bu işlemi onaylıyor musunuz? Geri alınamaz!')) {
                $this->info("İşlem iptal edildi.");
                return self::SUCCESS;
            }
        }

        $this->info("Birleştirme işlemi başlatılıyor...");

        DB::beginTransaction();

        try {
            // ---------------------------------------------------------
            // 1. BELGELER VE VERSİYONLAR
            // ---------------------------------------------------------
            $this->line("- Belgeler ve Versiyonlar aktarılıyor...");
            DB::table('documents')->where('created_by', $duplicateId)->update(['created_by' => $primaryId]);
            DB::table('documents')->where('locked_by', $duplicateId)->update(['locked_by' => $primaryId]);
            DB::table('documents')->where('delivered_to_user_id', $duplicateId)->update(['delivered_to_user_id' => $primaryId]);
            DB::table('document_versions')->where('created_by', $duplicateId)->update(['created_by' => $primaryId]);

            // ---------------------------------------------------------
            // 2. FİZİKSEL HAREKETLER VE ZİMMETLER
            // ---------------------------------------------------------
            $this->line("- Fiziksel zimmet geçmişi aktarılıyor...");
            DB::table('physical_movements')->where('sender_id', $duplicateId)->update(['sender_id' => $primaryId]);
            DB::table('physical_movements')->where('receiver_id', $duplicateId)->update(['receiver_id' => $primaryId]);

            // ---------------------------------------------------------
            // 3. VEKALETLER (DELEGATIONS)
            // ---------------------------------------------------------
            $this->line("- Vekaletnameler aktarılıyor...");
            DB::table('user_delegations')->where('delegator_id', $duplicateId)->update(['delegator_id' => $primaryId]);
            DB::table('user_delegations')->where('proxy_id', $duplicateId)->update(['proxy_id' => $primaryId]);

            // ---------------------------------------------------------
            // 4. ÇAKIŞMA RİSKİ OLAN TABLOLAR (UNIQUE CONSTRAINT KORUMASI)
            // ---------------------------------------------------------
            $this->line("- Onay akışları, Klasör Yetkileri ve Favoriler birleştiriliyor...");

            // A) Onaylar
            $this->mergeWithUniqueCheck('document_approvals', 'user_id', $duplicateId, $primaryId, ['document_id', 'step_order']);

            // B) Klasör Yetkileri
            $this->mergeWithUniqueCheck('folder_user_permissions', 'user_id', $duplicateId, $primaryId, ['folder_id']);

            // C) Favoriler
            $this->mergeWithUniqueCheck('document_user_favorites', 'user_id', $duplicateId, $primaryId, ['document_id']);

            // ---------------------------------------------------------
            // 5. BİLDİRİMLER VE SİSTEM LOGLARI (AUDIT)
            // ---------------------------------------------------------
            $this->line("- Bildirimler ve Loglar aktarılıyor...");
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $duplicateId)
                ->update(['notifiable_id' => $primaryId]);

            if (Schema::hasTable('audit_logs')) {
                DB::table('audit_logs')->where('user_id', $duplicateId)->update(['user_id' => $primaryId]);
            }
            if (Schema::hasTable('read_logs')) {
                DB::table('read_logs')->where('user_id', $duplicateId)->update(['user_id' => $primaryId]);
            }

            // ---------------------------------------------------------
            // 6. MÜKERRER KULLANICIYI BAĞLANTISIZ HALE GETİR VE SİL
            // ---------------------------------------------------------
            $duplicateUser->update([
                'email' => 'merged_' . time() . '_' . $duplicateUser->email,
                'tc_no' => null,
                'registration_no' => null,
                'mys_id' => null,
                'is_active' => false
            ]);

            // Spatie Roller ve yetkileri temizle
            $duplicateUser->syncRoles([]);

            // Soft delete
            $duplicateUser->delete();

            DB::commit();

            $this->info("✅ BİRLEŞTİRME BAŞARILI! Tüm veriler '{$primaryUser->name}' hesabına aktarıldı.");
            return self::SUCCESS;
        } catch (Exception $e) {
            DB::rollBack();
            $this->error("HATA: İşlem sırasında bir hata oluştu ve tüm değişiklikler geri alındı.");
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function mergeWithUniqueCheck(string $table, string $userColumn, int $duplicateId, int $primaryId, array $uniqueColumns)
    {
        $duplicateRecords = DB::table($table)->where($userColumn, $duplicateId)->get();

        foreach ($duplicateRecords as $record) {
            $query = DB::table($table)->where($userColumn, $primaryId);

            foreach ($uniqueColumns as $col) {
                $query->where($col, $record->$col);
            }

            $exists = $query->exists();

            if ($exists) {
                DB::table($table)->where('id', $record->id)->delete();
            } else {
                DB::table($table)->where('id', $record->id)->update([$userColumn => $primaryId]);
            }
        }
    }
}
