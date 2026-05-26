<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        // هنضيف العمود ويكون nullable عشان مش كل الناس هتعمل register من الموبايل علطول
        $table->string('device_token')->nullable()->after('email'); 
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('device_token');
    });
}
};
