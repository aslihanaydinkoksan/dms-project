<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_templates', function (Blueprint $table) {
            // Sıkı Mod Kalkanı: Dışarıdan ekstra ekip kurulabilsin mi? Default: true (Esnek)
            $table->boolean('allow_ad_hoc_members')->default(true)->after('mandatory_user_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('process_templates', function (Blueprint $table) {
            $table->dropColumn('allow_ad_hoc_members');
        });
    }
};
