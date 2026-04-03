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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            // Ayarın anahtarı (Örn: allowed_mimes, privacy_levels, max_file_size)
            $table->string('key')->unique();
            // JSON formatında tutarak array'leri kolayca saklayacağız
            $table->json('value');
            // Yönetici arayüzünde bu ayarın ne işe yaradığını açıklamak için
            $table->string('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
