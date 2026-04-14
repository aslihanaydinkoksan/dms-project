<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('folder_user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('folders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Klasör Yetkileri: Sadece okuma, Yükleme yapabilme, Tam yönetim (Klasör silme vb.)
            $table->enum('access_level', ['read', 'upload', 'manage']);
            $table->timestamps();

            // Bir klasörde bir kullanıcıya birden fazla yetki satırı açılamasın (Zırh)
            $table->unique(['folder_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('folder_user_permissions');
    }
};
