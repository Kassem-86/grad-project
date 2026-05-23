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
        Schema::table('selected_medications', function (Blueprint $table) {
            $table->dropForeign(['medication_id']);
            $table->dropColumn('medication_id');
            
            $table->unique(['user_id', 'medication_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('selected_medications', function (Blueprint $table) {
            $table->unsignedBigInteger('medication_id')->nullable();
            $table->foreign('medication_id')->references('medication_id')->on('record_medications')->onDelete('cascade');
            
            $table->dropUnique(['user_id', 'medication_name']);
        });
    }
};
