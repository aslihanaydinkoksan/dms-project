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
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Örn: Sözleşme, Prosedür, Talimat, Form
            $table->string('slug')->unique(); // Örn: sozlesme, prosedur (URL'ler ve Yetkiler için)
            $table->text('description')->nullable(); // Opsiyonel açıklama
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
