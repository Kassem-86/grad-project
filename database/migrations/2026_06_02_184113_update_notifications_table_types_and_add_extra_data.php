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
        if (!Schema::hasColumn('notifications', 'extra_data')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->json('extra_data')->nullable()->after('type');
            });
        }

        // Delete old 'community' notifications so ENUM strictly applies
        DB::statement("DELETE FROM notifications WHERE type = 'community'");

        // Use raw query for enum modification to avoid doctrine/dbal issues
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('reminder', 'chat', 'like', 'comment', 'alert', 'friend_request') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('reminder', 'community', 'chat', 'friend_request') NOT NULL");

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('extra_data');
        });
    }
};
