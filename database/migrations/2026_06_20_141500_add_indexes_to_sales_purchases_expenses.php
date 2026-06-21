<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add compound indexes on [user_id, date_time] for sales, purchases,
     * and cash_transactions — and [user_id, date] for expenses.
     *
     * Without these, any whereDate/whereYear query on a 1M-row table
     * forces a full table scan regardless of user_id filtering.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['user_id', 'date_time'], 'sales_user_date_idx');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index(['user_id', 'date_time'], 'purchases_user_date_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            // expenses uses a DATE column named 'date', not 'date_time'
            $table->index(['user_id', 'date'], 'expenses_user_date_idx');
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->index(['user_id', 'date_time'], 'cash_tx_user_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_user_date_idx');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_user_date_idx');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_user_date_idx');
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropIndex('cash_tx_user_date_idx');
        });
    }
};
