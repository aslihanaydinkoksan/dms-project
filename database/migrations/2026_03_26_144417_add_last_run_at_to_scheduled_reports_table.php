<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_reports', function (Blueprint $table) {
            // Raporun en son ne zaman çalıştığını tutacağımız alan
            $table->timestamp('last_run_at')->nullable()->after('frequency');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_reports', function (Blueprint $table) {
            $table->dropColumn('last_run_at');
        });
    }
};
