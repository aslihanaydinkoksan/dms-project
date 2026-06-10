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
        Schema::create('task_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // İşlemi kim yaptı? (Boşsa Sistemdir)
            $table->string('action'); // stage_changed, status_changed, notification_sent vb.
            $table->text('description'); // Okunabilir açıklama
            $table->json('old_data')->nullable(); // Eski değer
            $table->json('new_data')->nullable(); // Yeni değer
            $table->string('ip_address')->nullable(); // İşlemin yapıldığı IP
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_logs');
    }
};
