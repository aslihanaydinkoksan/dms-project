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
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_name');
            $table->string('module'); // documents, workflows vs.
            $table->string('frequency'); // daily, weekly, monthly
            $table->string('date_range'); // last_24_hours vs.
            $table->string('format'); // excel, pdf
            $table->text('recipients'); // Virgülle ayrılmış mailler
            $table->timestamp('last_sent_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
