<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('record_meals')) {
            Schema::table('record_meals', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('log_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                
                $table->string('meal_description', 50)->nullable()->after('user_id');
                
                if (!Schema::hasColumn('record_meals', 'notes')) {
                    $table->text('notes')->nullable()->after('meal_description');
                }
                
                $table->dropColumn(['meal_time', 'created_at', 'updated_at']);
            });
        } else {
            Schema::create('record_meals', function (Blueprint $table) {
                $table->id('meal_id');
                $table->unsignedBigInteger('log_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->float('total_carb')->nullable();
                $table->float('total_calories')->nullable();
                $table->string('meal_type', 50)->nullable();
                $table->string('meal_description', 50)->nullable();
                $table->text('notes')->nullable();
                
                $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        // just dropping it for symmetry
        Schema::dropIfExists('record_meals');
    }
};

