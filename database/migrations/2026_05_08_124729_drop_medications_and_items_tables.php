<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('medication_logs');
        Schema::dropIfExists('medications');
        Schema::dropIfExists('record_meal_food_items');
    }

    public function down(): void
    {
        // Intentionally empty or recreate them if needed
    }
};

