<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['category', 'sub_category']);
        });
    }

    public function down()
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('category')->nullable();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->string('sub_category')->nullable();
        });
    }
};
