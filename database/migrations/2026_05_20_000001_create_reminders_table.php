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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->enum('message_type', ['medication', 'glucose_check', 'meal']);
            $table->string('medication_name')->nullable();
            $table->dateTime('time');
            $table->enum('status', ['Still', 'Done', 'Skipped'])->default('Still');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['time', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
