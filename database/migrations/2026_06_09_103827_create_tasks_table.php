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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_template_id')->constrained()->onDelete('restrict');
            $table->foreignId('current_stage_id')->nullable()->constrained('process_stages')->onDelete('set null');
            $table->foreignId('creator_id')->constrained('users')->onDelete('restrict');
            $table->string('title');
            $table->json('custom_data'); // Şablondaki field değerlerinin dinamik saklandığı yer
            $table->string('status')->default('active'); // active, pending_closure_approval, completed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
