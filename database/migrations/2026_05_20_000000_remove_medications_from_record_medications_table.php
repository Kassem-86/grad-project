<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('record_medications', function (Blueprint $table) {
            $table->dropColumn('medications');
        });
    }

    public function down(): void
    {
        Schema::table('record_medications', function (Blueprint $table) {
            $table->json('medications')->nullable()->after('notes');
        });
    }
};
