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
        Schema::create('medication_log_pivot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('record_medication_id');
            $table->uuid('selected_medication_id');
            $table->timestamps();

            $table->foreign('record_medication_id')->references('medication_id')->on('record_medications')->onDelete('cascade');
            $table->foreign('selected_medication_id')->references('selected_med_id')->on('selected_medications')->onDelete('cascade');
            
            $table->unique(['record_medication_id', 'selected_medication_id'], 'med_log_pivot_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_log_pivot');
    }
};
