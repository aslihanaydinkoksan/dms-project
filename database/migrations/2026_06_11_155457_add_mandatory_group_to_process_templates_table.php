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
        Schema::table('process_templates', function (Blueprint $table) {
            $table->foreignId('mandatory_user_group_id')->nullable()->after('department_id')->constrained('user_groups')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('process_templates', function (Blueprint $table) {
            //
        });
    }
};
