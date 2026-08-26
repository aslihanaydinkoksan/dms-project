<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. YENİ VERSİYON TABLOSUNU OLUŞTUR
        Schema::create('document_attachment_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_attachment_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number')->default(1);
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->foreignId('created_by')->constrained('users');
            $table->boolean('is_current')->default(true);
            $table->text('revision_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. ESKİ TABLOYA METADATA SÜTUNLARINI EKLE
        Schema::table('document_attachments', function (Blueprint $table) {
            $table->string('title')->nullable()->after('document_id');
            $table->text('description')->nullable()->after('title');
        });

        // 3. VERİ KURTARMA OPERASYONU (Eski dosyaları V1 olarak yeni tabloya taşıyoruz)
        $oldAttachments = DB::table('document_attachments')->get();
        foreach ($oldAttachments as $att) {
            DB::table('document_attachment_versions')->insert([
                'document_attachment_id' => $att->id,
                'version_number' => 1,
                'original_name' => $att->original_name,
                'file_path' => $att->file_path,
                'mime_type' => $att->mime_type,
                'file_size' => $att->file_size,
                'created_by' => $att->uploaded_by,
                'is_current' => true,
                'created_at' => $att->created_at,
                'updated_at' => $att->updated_at,
            ]);

            // Ek belgenin başlığı boş kalmasın diye eski dosya adını başlık yapıyoruz
            DB::table('document_attachments')
                ->where('id', $att->id)
                ->update(['title' => $att->original_name]);
        }

        // 4. ESKİ TABLODAKİ GEREKSİZ FİZİKSEL SÜTUNLARI TEMİZLE
        Schema::table('document_attachments', function (Blueprint $table) {
            $table->dropColumn(['original_name', 'file_path', 'file_size', 'mime_type']);
        });
    }

    public function down(): void
    {
        // Rollback durumunda eski sütunları geri getir
        Schema::table('document_attachments', function (Blueprint $table) {
            $table->string('original_name')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->dropColumn(['title', 'description']);
        });

        Schema::dropIfExists('document_attachment_versions');
    }
};