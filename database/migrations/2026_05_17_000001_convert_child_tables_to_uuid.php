<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Convert child tables to use UUID for log_id foreign key.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Convert record_glucose table
        try {
            Schema::table('record_glucose', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // FK doesn't exist, continue
        }
        
        DB::statement('ALTER TABLE `record_glucose` MODIFY `log_id` CHAR(36) NOT NULL');
        
        Schema::table('record_glucose', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });

        // Convert record_meals table
        try {
            Schema::table('record_meals', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // FK doesn't exist, continue
        }
        
        DB::statement('ALTER TABLE `record_meals` MODIFY `log_id` CHAR(36) NOT NULL');
        
        Schema::table('record_meals', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });

        // Convert record_medications table
        try {
            Schema::table('record_medications', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // FK doesn't exist, continue
        }
        
        DB::statement('ALTER TABLE `record_medications` MODIFY `log_id` CHAR(36) NOT NULL');
        
        Schema::table('record_medications', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        // Revert record_glucose
        try {
            Schema::table('record_glucose', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // FK doesn't exist, continue
        }
        
        DB::statement('ALTER TABLE `record_glucose` MODIFY `log_id` BIGINT UNSIGNED NOT NULL');
        
        Schema::table('record_glucose', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });

        // Revert record_meals
        try {
            Schema::table('record_meals', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // FK doesn't exist, continue
        }
        
        DB::statement('ALTER TABLE `record_meals` MODIFY `log_id` BIGINT UNSIGNED NOT NULL');
        
        Schema::table('record_meals', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });

        // Revert record_medications
        try {
            Schema::table('record_medications', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // FK doesn't exist, continue
        }
        
        DB::statement('ALTER TABLE `record_medications` MODIFY `log_id` BIGINT UNSIGNED NOT NULL');
        
        Schema::table('record_medications', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }
};
