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
        Schema::table('documents', function (Blueprint $table) {
            // 1. Legal Kategorizasyon
            $table->string('category')->nullable()->after('document_type'); // Sözleşme, Vekaletname, İpotek vb.
            $table->string('sub_category')->nullable()->after('category'); // Örn: Kira Sözleşmesi, Gizlilik Sözleşmesi

            // 2. Sözleşme Meta Verileri
            $table->string('contract_party')->nullable()->after('sub_category'); // Karşı Taraf (Şirket/Kişi)
            $table->decimal('contract_amount', 15, 2)->nullable()->after('contract_party'); // Sözleşme Bedeli
            $table->string('contract_duration')->nullable()->after('contract_amount'); // Süre (Örn: 2 Yıl, Belirsiz)

            // 3. Fiziksel Arşiv ve Islak İmza Takibi (ÇOK KRİTİK)
            $table->string('physical_location')->nullable()->after('folder_id'); // Örn: Arşiv Odası B, Dolap 4, Klasör 12
            $table->foreignId('delivered_to_user_id')->nullable()->after('physical_location')->constrained('users')->nullOnDelete(); // Islak imzalı kopya şu an kimde?
            $table->enum('physical_receipt_status', ['pending', 'received', 'not_applicable'])->default('not_applicable')->after('delivered_to_user_id'); // Teslim alındı mı?
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['delivered_to_user_id']);
            $table->dropColumn([
                'category',
                'sub_category',
                'contract_party',
                'contract_amount',
                'contract_duration',
                'physical_location',
                'delivered_to_user_id',
                'physical_receipt_status'
            ]);
        });
    }
};
