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
            // LongText kullanıyoruz çünkü yüzlerce sayfalık bir belgenin metin verisi devasa olabilir
            $table->longText('ocr_text')->nullable()->after('validity_description')
                ->comment('Tesseract OCR tarafından taranan belge içi metin verisi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('ocr_text');
        });
    }
};
