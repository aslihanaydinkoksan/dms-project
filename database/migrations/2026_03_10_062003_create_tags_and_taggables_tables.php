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
        // 1. Ana Etiketler Tablosu
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Etiketler benzersiz olmalı (örn: "Önemli", "Finans")
            $table->timestamps();
        });

        // 2. Polymorphic Pivot Tablosu (Etiketlerin bağlandığı yer)
        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();

            // Bu tek satır 'taggable_id' ve 'taggable_type' kolonlarını otomatik oluşturur.
            $table->morphs('taggable');

            // Aynı belgeye aynı etiketin iki kez atanmasını engellemek için bileşik index
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};
