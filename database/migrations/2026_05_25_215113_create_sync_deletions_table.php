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
        Schema::create('sync_deletions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('table_name');
            $table->string('record_id'); // String to support both numerical IDs and UUIDs
            $table->timestamp('deleted_at')->useCurrent();

            // Assuming your users table primary key is 'id'. Alter if it is 'user_id'
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Helpful indices for fast sync lookups
            $table->index(['user_id', 'table_name', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_deletions');
    }
};
