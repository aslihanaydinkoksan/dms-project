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
        Schema::table('users', function (Blueprint $table) {
            // Sütunu ekliyoruz. Varsayılan olarak kimsenin yetkisi olmasın (false)
           // $table->boolean('can_manage_acl')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Geri alırsak sütunu sil
            $table->dropColumn('can_manage_acl');
        });
    }
};
