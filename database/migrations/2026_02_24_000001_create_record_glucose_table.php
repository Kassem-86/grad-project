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
        Schema::create('record_glucose', function (Blueprint $table) {
            $table->id('reading_id');
            $table->foreignId('log_id')->unique()->constrained('logs', 'log_id')->onDelete('cascade');          
            $table->float('glucose_level');
            $table->dateTime('reading_time');
            $table->enum('reading_type', ['Fasting', 'Before Meal', 'After Meal', 'Random']);
            $table->text('notes')->nullable();
            $table->float('a1c_estimation')->nullable();
            $table->float('average_glucose_level')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_glucose');
    }
};
