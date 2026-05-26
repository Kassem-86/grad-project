<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('selected_medications', function (Blueprint $table) {
            $table->uuid('selected_med_id')->primary();
            $table->unsignedBigInteger('medication_id')->nullable();
            // $table->unsignedBigInteger('log_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('medication_name', 50);

            $table->foreign('medication_id')->references('medication_id')->on('record_medications')->onDelete('cascade');
            // $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('selected_medications');
    }
};

