<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Durum: Normal Türkçe karakterlerle (Hizmete Özel) kaydedilmişse
        DB::table('system_settings')->update([
            'value' => DB::raw("REPLACE(value, 'Hizmete Özel', 'Departmana Özel')")
        ]);

        // 2. Durum: JSON encode edilirken Unicode olarak (Hizmete \u00d6zel) kaydedilmişse
        // Ters slash'in SQL'e doğru gitmesi için çift ters slash (\\) kullanıyoruz.
        DB::table('system_settings')->update([
            'value' => DB::raw("REPLACE(value, 'Hizmete \\u00d6zel', 'Departmana \\u00d6zel')")
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Geri alma işlemi (Rollback) gerekirse tam tersini yapıyoruz
        DB::table('system_settings')->update([
            'value' => DB::raw("REPLACE(value, 'Departmana Özel', 'Hizmete Özel')")
        ]);

        DB::table('system_settings')->update([
            'value' => DB::raw("REPLACE(value, 'Departmana \\u00d6zel', 'Hizmete \\u00d6zel')")
        ]);
    }
};
