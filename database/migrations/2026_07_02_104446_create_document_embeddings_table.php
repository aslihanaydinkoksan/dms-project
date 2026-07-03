<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


/**
 * Bu dosya, dokümanların parçalanmış metinlerini (chunk), 
 * bu parçaların hangi doküman/versiyona ait olduğunu ve 
 * en önemlisi "kimlerin okumaya yetkisi olduğunu" tutan MySQL tablosunu oluşturur.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_embeddings', function (Blueprint $table) {
            $table->id();

            // Kurumsal Yapı: Doküman ve Versiyon İlişkisi
            // Her vektör kesinlikle spesifik bir doküman versiyonuna aittir.
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('document_version_id')->constrained('document_versions')->onDelete('cascade');

            // RBAC (Yetki ve Gizlilik) Entegrasyonu
            // Semantik arama yaparken (WHERE clause) kullanıcının yetkisi olmayan belgeleri filtrelemek için.
            // Bu alanlar projenin büyüklüğüne göre nullable olabilir (örn: public belge ise).
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('privacy_level', 50)->nullable()->index()->comment('SystemSetting tablosundan gelen gizlilik seviyesi anahtarı');

            // Metin ve Vektör Bağlantısı
            $table->integer('chunk_index')->comment('Doküman içindeki parça sırası');
            $table->text('chunk_text')->comment('Vektörleştirilen orijinal metin parçası (Context olarak LLM e gidecek)');

            // Hibrit Mimari: Dışarıdaki (Örn: Qdrant) vektör veritabanındaki kaydın ID'si.
            $table->string('external_vector_id')->nullable()->unique()->comment('Vektör DB referans ID si');

            // Geriye dönük uyumluluk veya lokal testler için (isteğe bağlı) MySQL içinde JSON vektör tutma alanı.
            // Prod ortamında burası boş bırakılıp external_vector_id kullanılacaktır.
            $table->json('vector_data')->nullable()->comment('Lokal testler için opsiyonel JSON vektör verisi');

            // Metadata: Hangi modelle oluşturuldu? (İleride model güncellenirse eski vektörleri tespit etmek için)
            $table->string('embedding_model')->comment('Örn: text-embedding-3-small');
            $table->integer('token_count')->default(0)->comment('Maliyet ve limit hesabı için token sayısı');

            $table->timestamps();
            // Kurumsal Standart: Hiçbir veri fiziksel olarak silinmez.
            $table->softDeletes();

            // Performans için İndekslemeler
            $table->index(['document_id', 'document_version_id']);
            $table->index(['department_id', 'privacy_level']); // Yetki aramalarını hızlandırmak için
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_embeddings');
    }
};
