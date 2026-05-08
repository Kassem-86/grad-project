<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_medications', function (Blueprint $table) {
            $table->id('medication_id');
            $table->unsignedBigInteger('log_id');
            $table->unsignedBigInteger('user_id');
            $table->text('medications')->nullable();
            $table->text('notes')->nullable();

            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // if you need created_at / updated_at
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_medications');
    }
};

