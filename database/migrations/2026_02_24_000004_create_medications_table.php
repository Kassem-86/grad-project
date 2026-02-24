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
        Schema::create('medications', function (Blueprint $table) {
            $table->id('medication_id');
// التعديل الصح عشان يربط بـ log_id مش id
            $table->foreignId('log_id')->unique()->constrained('logs', 'log_id')->onDelete('cascade');            $table->string('medication_name');
            $table->string('dosage');
            $table->enum('route', ['Oral', 'Injection', 'Inhaler', 'Topical', 'IV']);
            $table->string('unit');
            $table->string('frequency');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('reminder_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
