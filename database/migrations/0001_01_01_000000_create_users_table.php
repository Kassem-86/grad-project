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
    Schema::create('users', function (Blueprint $table) {
        $table->id('id'); 
        $table->string('first_name', 50); 
        $table->string('last_name', 50); 
        $table->string('email', 50)->unique(); 
        $table->string('password'); 
        $table->enum('gender', ['Male', 'Female'])->nullable();
        $table->string('phone', 11)->nullable();
        $table->integer('year')->nullable();
        $table->integer('month')->nullable();
        $table->integer('day')->nullable();
        $table->enum('diabetes_type', ['Type1', 'Type2', 'LADA', 'MODY', 'Gestational', 'diabetes', 'other'])->nullable();
        $table->enum('insulin_therapy', ['Pen / Syringes', 'pump', 'No insulin'])->nullable();
        $table->dateTime('diagnose_date')->nullable();
        $table->enum('glucose', ['mg/dl', 'mmol/L'])->nullable();
        $table->float('weight')->nullable();
        $table->float('height')->nullable();
        $table->float('max_glucose')->nullable();
        $table->float('target_glucose_range')->nullable();
        $table->float('min_glucose')->nullable();
        $table->string('emergency_contact', 11)->nullable();
        
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};