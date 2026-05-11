<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix diabetes_type enum to match User::DIABETES_TYPES constant
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Drop the old enum and create a new one with correct values
                $table->dropColumn('diabetes_type');
                $table->enum('diabetes_type', User::DIABETES_TYPES)->nullable()->after('birthDate');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('diabetes_type');
                // Revert to old structure
                $table->enum('diabetes_type', ['Type1', 'LADA', 'Type2', 'MODY', 'Gestational'])->nullable();
            });
        }
    }
};
