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
        Schema::create('process_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_template_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Örn: "Onay Bekliyor", "İşleme Alındı"
            $table->string('color')->nullable(); // Kanban kart başlığı / sütun rengi
            $table->integer('sort_order'); // Sütun sıralaması
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_stages');
    }
};
