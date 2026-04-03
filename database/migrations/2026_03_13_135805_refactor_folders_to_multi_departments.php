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
        // 1. Eski dar vizyonlu kolonu uçuruyoruz (Eğer daha önce eklediysen)
        if (Schema::hasColumn('folders', 'department_id')) {
            Schema::table('folders', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
        }

        // 2. Yeni Özgür Dünya: Pivot Tablo (Many-to-Many)
        Schema::create('department_folder', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            // Aynı departman aynı klasöre 2 kez eklenemesin
            $table->unique(['department_id', 'folder_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_folder');
        Schema::table('folders', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
        });
    }
};
