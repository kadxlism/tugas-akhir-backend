<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'password_hash')) {
                $table->renameColumn('password_hash', 'password');
            }
        });

        // Adjust role enum to ensure allowed values
        Schema::table('users', function (Blueprint $table) {
            // For MySQL, enum alteration requires column redefinition
            $table->enum('role', ['admin', 'designer', 'copywriter', 'web_designer'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'password')) {
                $table->renameColumn('password', 'password_hash');
            }
        });
        Schema::table('users', function (Blueprint $table) {
            // Revert to previous allowed values if different; keep same for safety
            $table->enum('role', ['admin', 'designer', 'copywriter', 'web_designer'])->change();
        });
    }
};

