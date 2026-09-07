<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class MergeUsersCommand extends Command
{
    protected $signature = 'dms:merge-users 
                            {duplicate_id : Mükerrer olan ve silinecek (eski/hatalı) kullanıcının IDsi} 
                            {primary_id : Asıl olan ve verilerin aktarılacağı (kalacak) kullanıcının IDsi}
                            {--force : Onay sormadan işlemi direkt yapar}';

    protected $description = 'Mükerrer kullanıcının tüm verilerini asıl kullanıcıya aktarır ve mükerrer hesabı güvenle siler.';

    public function handle()
    {
        $duplicateId = $this->argument('duplicate_id');
        $primaryId = $this->argument('primary_id');

        if ($duplicateId == $primaryId) {
            $this->error("HATA: Mükerrer ID ile Asıl ID aynı olamaz!");
            return self::FAILURE;
        }

        $duplicateUser = User::withTrashed()->find($duplicateId);
        $primaryUser = User::withTrashed()->find($primaryId);

        if (!$duplicateUser || !$primaryUser) {
            $this->error("HATA: Kullanıcılardan biri bulunamadı!");
            return self::FAILURE;
        }

        if (!$this->option('force')) {
            $this->warn("DİKKAT: '{$duplicateUser->name}' (ID: {$duplicateId}) kullanıcısının TÜM VERİLERİ");
            $this->warn("'{$primaryUser->name}' (ID: {$primaryId}) kullanıcısına aktarılacak.");
            
            if (!$this->confirm('Bu işlemi onaylıyor musunuz? Geri alınamaz!')) {
                return self::SUCCESS;
            }
        }

        DB::beginTransaction();

        try {
            // 1. BELGELER VE VERSİYONLAR
            if (Schema::hasTable('documents')) {
                DB::table('documents')->where('locked_by', $duplicateId)->update(['locked_by' => $primaryId]);
                DB::table('documents')->where('delivered_to_user_id', $duplicateId)->update(['delivered_to_user_id' => $primaryId]);
            }
            if (Schema::hasTable('document_versions')) {
                DB::table('document_versions')->where('created_by', $duplicateId)->update(['created_by' => $primaryId]);
            }

            // 2. FİZİKSEL HAREKETLER (Varsa)
            if (Schema::hasTable('physical_movements')) {
                DB::table('physical_movements')->where('sender_id', $duplicateId)->update(['sender_id' => $primaryId]);
                DB::table('physical_movements')->where('receiver_id', $duplicateId)->update(['receiver_id' => $primaryId]);
            }

            // 3. VEKALETLER (Varsa)
            if (Schema::hasTable('user_delegations')) {
                DB::table('user_delegations')->where('delegator_id', $duplicateId)->update(['delegator_id' => $primaryId]);
                DB::table('user_delegations')->where('proxy_id', $duplicateId)->update(['proxy_id' => $primaryId]);
            }

            // 4. ÇAKIŞMA RİSKİ OLAN TABLOLAR (UNIQUE CHECK)
            if (Schema::hasTable('document_approvals')) {
                $this->mergeWithUniqueCheck('document_approvals', 'user_id', $duplicateId, $primaryId, ['document_id', 'step_order']);
            }
            if (Schema::hasTable('folder_user_permissions')) {
                $this->mergeWithUniqueCheck('folder_user_permissions', 'user_id', $duplicateId, $primaryId, ['folder_id']);
            }
            if (Schema::hasTable('document_user_favorites')) {
                $this->mergeWithUniqueCheck('document_user_favorites', 'user_id', $duplicateId, $primaryId, ['document_id']);
            }

            // 5. BİLDİRİMLER VE SİSTEM LOGLARI
            if (Schema::hasTable('notifications')) {
                DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $duplicateId)
                    ->update(['notifiable_id' => $primaryId]);
            }
            if (Schema::hasTable('audit_logs')) {
                DB::table('audit_logs')->where('user_id', $duplicateId)->update(['user_id' => $primaryId]);
            }
            if (Schema::hasTable('read_logs')) {
                DB::table('read_logs')->where('user_id', $duplicateId)->update(['user_id' => $primaryId]);
            }

            // 6. MÜKERRER KULLANICIYI BAĞLANTISIZ HALE GETİR VE SİL
            $duplicateUser->update([
                'email' => 'merged_' . time() . '_' . $duplicateUser->email,
                'tc_no' => null,
                'registration_no' => null,
                'is_active' => false
            ]);

            $duplicateUser->syncRoles([]);
            $duplicateUser->delete();

            DB::commit();
            return self::SUCCESS;

        } catch (Exception $e) {
            DB::rollBack();
            $this->error("HATA: " . $e->getMessage());
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

            if ($query->exists()) {
                DB::table($table)->where('id', $record->id)->delete();
            } else {
                DB::table($table)->where('id', $record->id)->update([$userColumn => $primaryId]);
            }
        }
    }
}