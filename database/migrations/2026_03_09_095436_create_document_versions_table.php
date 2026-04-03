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
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->restrictOnDelete();
            $table->string('version_number'); // Örn: "1.0", "1.1", "2.0"
            $table->string('file_path'); // Storage altındaki güvenli yol
            $table->string('mime_type'); // PDF, DOCX doğrulama için
            $table->unsignedBigInteger('file_size'); // Bayt cinsinden boyut

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            // Aktif versiyonu hızlıca bulmak için flag (Bayrak)
            $table->boolean('is_current')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
