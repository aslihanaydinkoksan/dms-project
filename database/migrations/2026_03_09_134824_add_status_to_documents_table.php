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
        Schema::table('documents', function (Blueprint $table) {
            // documents tablosunun sonuna, onay akışını yönetecek status kolonunu ekliyoruz.
            // Varsayılan değer 'draft' (taslak) olarak belirlendi.
            $table->string('status')->default('draft')->after('locked_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Rollback durumunda sadece bu kolonu uçurur.
            $table->dropColumn('status');
        });
    }
};
