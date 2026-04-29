<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. First add the new enum values
        DB::statement("ALTER TABLE posts MODIFY COLUMN category ENUM('General', 'Type 1', 'LADA', 'Type1 and LADA', 'Type 2', 'Type2', 'gestational', 'advices') DEFAULT 'General'");

        // 2. Update existing rows (map existing data)
        DB::statement("UPDATE posts SET category = 'Type 1' WHERE category = 'Type1 and LADA'");
        DB::statement("UPDATE posts SET category = 'Type 2' WHERE category = 'Type2'");

        // 3. Remove the old enum value
        DB::statement("ALTER TABLE posts MODIFY COLUMN category ENUM('General', 'Type 1', 'LADA', 'Type 2', 'gestational', 'advices') DEFAULT 'General'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE posts MODIFY COLUMN category ENUM('General', 'Type 1', 'LADA', 'Type1 and LADA', 'Type 2', 'Type2', 'gestational', 'advices') DEFAULT 'General'");

        DB::statement("UPDATE posts SET category = 'Type1 and LADA' WHERE category = 'Type 1' OR category = 'LADA'");
        DB::statement("UPDATE posts SET category = 'Type2' WHERE category = 'Type 2'");

        DB::statement("ALTER TABLE posts MODIFY COLUMN category ENUM('General', 'Type1 and LADA', 'Type2', 'gestational', 'advices') DEFAULT 'General'");
    }
};
