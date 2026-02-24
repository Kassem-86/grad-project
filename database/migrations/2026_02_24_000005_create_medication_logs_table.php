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
        Schema::create('medication_logs', function (Blueprint $table) {
            $table->id('medlog_id');
// التعديل الصح عشان يربط بـ medication_id مش id
            $table->foreignId('medication_id')->constrained('medications', 'medication_id')->onDelete('cascade');            $table->dateTime('taken_at');
            $table->enum('status', ['taken', 'missed', 'skipped']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_logs');
    }
};
