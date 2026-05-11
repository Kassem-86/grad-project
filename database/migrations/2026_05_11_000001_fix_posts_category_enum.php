<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Post;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix category enum to match Post::CATEGORIES constant
        if (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                // Drop the old enum and create a new one with correct values
                $table->dropColumn('category');
                $table->enum('category', Post::CATEGORIES)->after('content');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropColumn('category');
                // Revert to old structure (with errors from original migration)
                $table->enum('category', ['General', 'Type1 / LADA', 'Type2', 'MODY', 'Gestational', 'Advices']);
            });
        }
    }
};
