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
            $table->string('document_type')->nullable()->after('document_number'); // Talimat, Prosedür vb.
            $table->string('system_article_no')->nullable()->after('document_type'); // ISO Madde No
            $table->foreignId('related_department_id')->nullable()->after('folder_id')->constrained('departments')->nullOnDelete();
            
            // Saklama Süreleri (İmha Politikası İçin Hayati Öneme Sahip)
            $table->integer('department_retention_years')->default(0)->after('privacy_level'); 
            $table->integer('archive_retention_years')->default(0)->after('department_retention_years');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['related_department_id']);
            $table->dropColumn([
                'document_type', 
                'system_article_no', 
                'related_department_id', 
                'department_retention_years', 
                'archive_retention_years'
            ]);
        });
    }
};
