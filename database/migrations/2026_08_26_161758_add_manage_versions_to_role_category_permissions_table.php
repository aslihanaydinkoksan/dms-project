<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Matris tablosuna yeni sütunu ekle (can_delete'den hemen sonraya)
        Schema::table('role_category_permissions', function (Blueprint $table) {
            $table->boolean('can_manage_versions')->default(false)->after('can_delete');
        });

        // 2. Sisteme yeni Spatie İznini (Global Yetki) tanımla
        Permission::firstOrCreate([
            'name' => 'document.manage_versions', 
            'guard_name' => 'web'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback durumunda sütunu sil
        Schema::table('role_category_permissions', function (Blueprint $table) {
            $table->dropColumn('can_manage_versions');
        });

        // İzni sil
        Permission::where('name', 'document.manage_versions')->delete();
    }
};
