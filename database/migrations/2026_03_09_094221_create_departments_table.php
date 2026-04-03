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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Ağaç yapısı: Kök (Root) departmanların parent_id'si null olur.
            $table->foreignId('parent_id')->nullable()->constrained('departments')->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes(); // Veri kaybına son.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
