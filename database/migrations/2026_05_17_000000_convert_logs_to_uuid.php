<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Convert logs table to use UUID for log_id.
     */
    public function up(): void
    {
        // Create new logs table with UUID primary key
        Schema::create('logs_uuid', function (Blueprint $table) {
            $table->uuid('log_id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('log_title')->nullable();
            $table->text('log_description')->nullable();
            $table->dateTime('logged_at')->nullable();
            $table->timestamps();
        });

        // Copy data from old logs table to new one (if migration is run on existing data)
        DB::statement('INSERT INTO logs_uuid (log_id, user_id, log_title, log_description, logged_at, created_at, updated_at) 
                      SELECT CAST(log_id AS CHAR), user_id, log_title, log_description, logged_at, created_at, updated_at FROM logs');

        // Drop old logs table and related foreign keys from child tables
        Schema::table('record_glucose', function (Blueprint $table) {
            $table->dropForeign(['log_id']);
        });
        Schema::table('record_meals', function (Blueprint $table) {
            $table->dropForeign(['log_id']);
        });
        Schema::table('record_medications', function (Blueprint $table) {
            $table->dropForeign(['log_id']);
        });

        Schema::drop('logs');

        // Rename logs_uuid to logs
        Schema::rename('logs_uuid', 'logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Create old logs table with integer primary key
        Schema::create('logs_old', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('log_title')->nullable();
            $table->text('log_description')->nullable();
            $table->dateTime('logged_at')->nullable();
            $table->timestamps();
        });

        // Copy data back
        DB::statement('INSERT INTO logs_old (log_id, user_id, log_title, log_description, logged_at, created_at, updated_at) 
                      SELECT CAST(SUBSTR(log_id, 1, 19) AS UNSIGNED), user_id, log_title, log_description, logged_at, created_at, updated_at FROM logs LIMIT 9223372036854775807');

        // Drop new logs table
        Schema::drop('logs');

        // Rename logs_old to logs
        Schema::rename('logs_old', 'logs');

        // Re-add foreign keys
        Schema::table('record_glucose', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });
        Schema::table('record_meals', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });
        Schema::table('record_medications', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });
    }
};
