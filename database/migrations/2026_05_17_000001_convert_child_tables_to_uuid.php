<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Convert child tables to use UUID for log_id foreign key.
     */
    public function up(): void
    {
        // Convert record_glucose table
        Schema::table('record_glucose', function (Blueprint $table) {
            $table->dropForeign(['log_id']);
            $table->dropColumn('log_id');
        });
        Schema::table('record_glucose', function (Blueprint $table) {
            $table->uuid('log_id')->unique()->after('reading_id');
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });

        // Convert record_meals table
        Schema::table('record_meals', function (Blueprint $table) {
            $table->dropForeign(['log_id']);
            $table->dropColumn('log_id');
        });
        Schema::table('record_meals', function (Blueprint $table) {
            $table->uuid('log_id')->unique()->after('meal_id');
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });

        // Convert record_medications table
        Schema::table('record_medications', function (Blueprint $table) {
            $table->dropForeign(['log_id']);
            $table->dropColumn('log_id');
        });
        Schema::table('record_medications', function (Blueprint $table) {
            $table->uuid('log_id')->unique()->after('medication_id');
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert record_glucose
        Schema::table('record_glucose', function (Blueprint $table) {
            $table->dropForeign(['log_id']);
            $table->dropColumn('log_id');
        });
        Schema::table('record_glucose', function (Blueprint $table) {
            $table->foreignId('log_id')->unique()->after('reading_id');
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });

        // Revert record_meals
        Schema::table('record_meals', function (Blueprint $table) {
            $table->dropForeign(['log_id']);
            $table->dropColumn('log_id');
        });
        Schema::table('record_meals', function (Blueprint $table) {
            $table->foreignId('log_id')->unique()->after('meal_id');
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });

        // Revert record_medications
        Schema::table('record_medications', function (Blueprint $table) {
            $table->dropForeign(['log_id']);
            $table->dropColumn('log_id');
        });
        Schema::table('record_medications', function (Blueprint $table) {
            $table->foreignId('log_id')->unique()->after('medication_id');
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });
    }
};
