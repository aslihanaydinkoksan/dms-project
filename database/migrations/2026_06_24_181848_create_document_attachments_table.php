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
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            // Ana belgeye bağlanan Foreign Key. Ana belge fiziksel silinirse ekler de silinir (cascade).
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();

            // Orijinal dosya adı (Kullanıcı deneyimi için kritik)
            $table->string('original_name');
            // Güvenli klasördeki fiziksel yolu
            $table->string('file_path');
            // Dosya boyutu (bayt cinsinden, raporlama için faydalı)
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100);

            // Yükleyen kullanıcı bilgisi
            $table->foreignId('uploaded_by')->constrained('users');

            $table->timestamps();
            // Veri bütünlüğü için fiziksel silmeyi engelleyen SoftDelete kalkanı
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_attachments');
    }
};
