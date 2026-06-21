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
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['user_id', 'total_due']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->index(['user_id', 'total_due']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'total_due']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'total_due']);
        });
    }
};
