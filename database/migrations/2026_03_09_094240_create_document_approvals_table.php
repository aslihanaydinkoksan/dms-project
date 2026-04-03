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
        Schema::create('document_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id'); // Doküman tablosunu ileride kuracağımız için constraint yok.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // Veritabanı seviyesinde ENUM kullanmaktan kaçınıyoruz, PHP tarafında Enum class'ları ile yöneteceğiz.
            $table->string('status')->default('pending'); // pending, approved, rejected

            // Sıralı ve paralel onay akışını bu alanla çözeceğiz.
            $table->integer('step_order')->default(1);

            $table->text('comment')->nullable();
            $table->timestamp('action_date')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_approvals');
    }
};
