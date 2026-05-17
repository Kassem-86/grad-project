<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Convert logs table to use UUID for log_id.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Create new logs table with UUID primary key (without foreign key initially)
        Schema::create('logs_uuid', function (Blueprint $table) {
            $table->uuid('log_id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('log_title')->nullable();
            $table->text('log_description')->nullable();
            $table->dateTime('logged_at')->nullable();
            $table->timestamps();
        });

        // Copy data from old logs table to new one
        DB::statement('INSERT INTO logs_uuid (log_id, user_id, log_title, log_description, logged_at, created_at, updated_at) 
                      SELECT UUID(), user_id, log_title, log_description, logged_at, created_at, updated_at FROM logs');

        // Drop old logs table and related foreign keys from child tables
        try {
            Schema::table('record_glucose', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // constraint may not exist — continue
        }
        try {
            Schema::table('record_meals', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // continue
        }
        try {
            Schema::table('record_medications', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // continue
        }
        try {
            Schema::table('medications', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // continue
        }
        try {
            Schema::table('selected_medications', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // continue
        }

        Schema::drop('logs');

        // Rename logs_uuid to logs
        Schema::rename('logs_uuid', 'logs');

        // Add foreign key to logs table
        Schema::table('logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        // Drop foreign keys from child tables before modifying
        try {
            Schema::table('record_glucose', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // continue
        }
        try {
            Schema::table('record_meals', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // continue
        }
        try {
            Schema::table('record_medications', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // continue
        }
        try {
            Schema::table('medications', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // continue
        }
        try {
            Schema::table('selected_medications', function (Blueprint $table) {
                $table->dropForeign(['log_id']);
            });
        } catch (\Exception $e) {
            // continue
        }

        // Create old logs table with integer primary key (without foreign key initially)
        Schema::create('logs_old', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('user_id');
            $table->string('log_title')->nullable();
            $table->text('log_description')->nullable();
            $table->dateTime('logged_at')->nullable();
            $table->timestamps();
        });

        // Copy data back (convert UUID to integer - this assumes a numeric conversion strategy)
        DB::statement('INSERT INTO logs_old (log_id, user_id, log_title, log_description, logged_at, created_at, updated_at) 
                      SELECT user_id, user_id, log_title, log_description, logged_at, created_at, updated_at FROM logs');

        // Drop new logs table
        Schema::drop('logs');

        // Rename logs_old to logs
        Schema::rename('logs_old', 'logs');

        // Add foreign key to restored logs table
        Schema::table('logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Re-add foreign keys on child tables pointing to logs
        Schema::table('record_glucose', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });
        Schema::table('record_meals', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });
        Schema::table('record_medications', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });
        Schema::table('medications', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });
        Schema::table('selected_medications', function (Blueprint $table) {
            $table->foreign('log_id')->references('log_id')->on('logs')->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }
};
