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
        Schema::table('clients', function (Blueprint $table) {
            // Rename name to company_name if it exists
            if (Schema::hasColumn('clients', 'name') && !Schema::hasColumn('clients', 'company_name')) {
                $table->renameColumn('name', 'company_name');
            }

            // Rename owner_name to owner if it exists
            if (Schema::hasColumn('clients', 'owner_name') && !Schema::hasColumn('clients', 'owner')) {
                $table->renameColumn('owner_name', 'owner');
            }

            // Add missing columns if they don't exist
            if (!Schema::hasColumn('clients', 'package')) {
                $table->string('package')->after('phone');
            }

            if (!Schema::hasColumn('clients', 'deadline')) {
                $table->date('deadline')->after('package');
            }

            if (!Schema::hasColumn('clients', 'dp')) {
                $table->string('dp')->after('deadline');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Reverse renames
            if (Schema::hasColumn('clients', 'company_name') && !Schema::hasColumn('clients', 'name')) {
                $table->renameColumn('company_name', 'name');
            }

            if (Schema::hasColumn('clients', 'owner') && !Schema::hasColumn('clients', 'owner_name')) {
                $table->renameColumn('owner', 'owner_name');
            }

            // Drop added columns
            if (Schema::hasColumn('clients', 'package')) {
                $table->dropColumn('package');
            }

            if (Schema::hasColumn('clients', 'deadline')) {
                $table->dropColumn('deadline');
            }

            if (Schema::hasColumn('clients', 'dp')) {
                $table->dropColumn('dp');
            }
        });
    }
};
