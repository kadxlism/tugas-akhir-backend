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
        Schema::create('time_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('task_id'); // Changed to unsignedBigInteger to match tasks table
            $table->unsignedBigInteger('user_id'); // Changed to unsignedBigInteger to match users table
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('paused_at')->nullable();
            $table->integer('paused_duration_minutes')->default(0);
            $table->timestamps();

            // Note: Foreign key constraints commented out because tasks/users tables use integer IDs
            // Uncomment these lines after converting tasks/users tables to use UUIDs
            // $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('task_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_logs');
    }
};
