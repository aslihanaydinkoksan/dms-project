<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seviye: Ana Departman (Root - parent_id null olacak)
        $board = Department::firstOrCreate(['name' => 'Yönetim Kurulu']);

        // 2. Seviye: Alt Departmanlar
        $it = Department::firstOrCreate([
            'name' => 'Bilgi Teknolojileri',
            'parent_id' => $board->id
        ]);

        $hr = Department::firstOrCreate([
            'name' => 'İnsan Kaynakları',
            'parent_id' => $board->id
        ]);

        // 3. Seviye: Altın Altı (N-Derinlik Testi İçin)
        Department::firstOrCreate([
            'name' => 'Yazılım Geliştirme',
            'parent_id' => $it->id
        ]);
        
        Department::firstOrCreate([
            'name' => 'Sistem ve Ağ Yönetimi',
            'parent_id' => $it->id
        ]);
    }
}