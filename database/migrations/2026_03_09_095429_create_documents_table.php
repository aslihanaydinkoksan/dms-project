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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('folders')->restrictOnDelete();
            $table->string('title');
            $table->string('document_number')->unique(); // Evrak kayıt numarası benzersiz olmalı.

            // Gizlilik seviyeleri: public, confidential, strictly_confidential vb. 
            // (Veritabanında ENUM yerine string tutuyoruz, validasyonu PHP tarafında yapacağız)
            $table->string('privacy_level')->default('confidential');

            // Check-out / Kilitleme Mekanizması
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        // Önceki adımda bekleyen document_approvals tablosundaki document_id için Foreign Key ekleyelim
        Schema::table('document_approvals', function (Blueprint $table) {
            $table->foreign('document_id')->references('id')->on('documents')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_approvals', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
        });
        Schema::dropIfExists('documents');
    }
};
