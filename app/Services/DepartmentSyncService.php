<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Exception;

class DepartmentSyncService
{
    protected MysApiService $mysApi;

    public function __construct(MysApiService $mysApi)
    {
        $this->mysApi = $mysApi;
    }

    /**
     * Departmanları senkronize eder.
     */
    public function sync(): int
    {
        $departments = $this->mysApi->getDepartments();
        $syncedCount = 0;

        DB::transaction(function () use ($departments, &$syncedCount) {
            $parentUpdates = [];

            // 1. Tur: Tüm departmanları kaydet/güncelle (Foreign Key hatası almamak için parent_id işlenmez)
            foreach ($departments as $deptData) {
                Department::updateOrCreate(
                    ['id' => $deptData['id']],
                    [
                        'name' => $deptData['name'],
                        // unit, requires_approval_on_upload gibi DMS'ye özel alanlar 
                        // updateOrCreate'de ezilmesin diye buraya eklemiyoruz.
                    ]
                );

                // İlişkiyi hafızaya al
                if (!empty($deptData['parent_id'])) {
                    $parentUpdates[$deptData['id']] = $deptData['parent_id'];
                }

                $syncedCount++;
            }

            // 2. Tur: Tüm departmanlar veritabanına girdiği için artık güvenle Parent ID'leri güncelleyebiliriz
            foreach ($parentUpdates as $id => $parentId) {
                Department::where('id', $id)->update(['parent_id' => $parentId]);
            }
        });

        return $syncedCount;
    }
}