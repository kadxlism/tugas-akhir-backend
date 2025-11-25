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
        Schema::table('projects', function (Blueprint $table) {
            // Add client_table_id to link projects to clients table
            if (!Schema::hasColumn('projects', 'client_table_id')) {
                $table->unsignedBigInteger('client_table_id')->nullable()->after('client_id');
                $table->foreign('client_table_id')->references('id')->on('clients')->onDelete('set null');
                $table->index('client_table_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'client_table_id')) {
                $table->dropForeign(['client_table_id']);
                $table->dropIndex(['client_table_id']);
                $table->dropColumn('client_table_id');
            }
        });
    }
};
