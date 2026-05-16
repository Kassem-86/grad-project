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
        // استخدام Raw Query هو الأضمن والأسرع لتعديل الـ Enum من غير مشاكل Doctrine DBAL
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('reminder', 'community', 'chat', 'friend_request') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // في حالة الـ Rollback بنرجع الـ Enum القديم زي ما كان
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('reminder', 'community', 'chat') NOT NULL");
    }
};