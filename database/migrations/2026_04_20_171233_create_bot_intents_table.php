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
        Schema::create('bot_intents', function (Blueprint $table) {
            $table->id();
            $table->string('intent_name'); // Örn: Belge Yükleme
            $table->json('keywords'); // Örn: ["yükle", "yeni belge", "evrak ekle"]
            $table->text('response_text'); // Örn: "Belge yükleme sayfasına buradan ulaşabilirsiniz."
            $table->string('action_route')->nullable(); // Örn: "documents.create"
            $table->string('action_button_text')->nullable(); // Örn: "Belge Yükle"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_intents');
    }
};
