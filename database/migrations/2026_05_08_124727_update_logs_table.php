<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->string('log_title', 50)->nullable()->after('user_id');
            $table->text('log_description')->nullable()->after('log_title');
            $table->dateTime('logged_at')->useCurrent()->after('log_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropColumn(['log_title', 'log_description', 'logged_at']);
        });
    }
};
