<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('folder_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('folders')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete(); // Spatie Rol tablosu

            // Yetki Kırılımları (Granular Permissions)
            $table->boolean('can_view')->default(false);
            $table->boolean('can_upload')->default(false);
            $table->boolean('can_create_subfolder')->default(false);
            $table->boolean('can_manage')->default(false); // Düzenleme ve Silme

            $table->timestamps();

            // Bir klasörde bir rolün yalnızca bir ayar kaydı olabilir
            $table->unique(['folder_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_role_permissions');
    }
};
