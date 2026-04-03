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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // İşlemi yapan kullanıcı. Sistem logları (cron vb.) için nullable yapıyoruz.
            // Silindiğinde loglar kalsın diye nullOnDelete kullanıyoruz.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // İşlem türü: created, updated, deleted, viewed, downloaded, locked
            $table->string('event');
            
            // Polymorphic alanlar: auditable_type (Model sınıfı) ve auditable_id (Kayıt ID'si)
            // Bu tek satır, hem auditable_type hem de auditable_id sütunlarını otomatik oluşturur ve indexler.
            $table->morphs('auditable');
            
            // Değişiklik detayları (Sadece değişen alanları tutacağız, tüm satırı değil)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            
            // İzlenebilirlik ve güvenlik için ekstra bilgiler
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Updated_at'e ihtiyacımız yok çünkü loglar güncellenmez, sadece oluşturulur.
            // Ancak standartları bozmamak adına timestamps() bırakıyoruz.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
