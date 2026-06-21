<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // إضافة النوع الجديد accepted للـ Enum
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('reminder', 'community', 'chat', 'friend_request', 'accepted', 'rejected') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // دي عشان لو عملت rollback يرجع النوع القديم
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('reminder', 'community', 'chat', 'friend_request') NOT NULL");
    }
};