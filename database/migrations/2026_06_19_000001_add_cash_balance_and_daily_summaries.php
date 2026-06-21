<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds two things:
     *
     *  1. users.cash_balance — a maintained running total of all cash-in minus
     *     cash-out for each user.  Updated atomically by CashTransactionObserver
     *     on every create/update/delete, so it is ALWAYS correct and reading it
     *     is a single field lookup (O(1)) instead of a full-table SUM.
     *
     *  2. daily_cash_summaries — stores the aggregated cash-in and cash-out
     *     totals per user per day.  Used to derive the opening balance for any
     *     selected date without scanning raw transaction rows.
     *
     *     Opening balance for date D:
     *       = users.cash_balance
     *         - SUM(daily_cash_summaries.cash_in  - daily_cash_summaries.cash_out)
     *           WHERE user_id = X AND date >= D
     *
     *     Editing a past transaction only requires updating ONE row in this table
     *     (the affected day) — no cascade to future days ever needed.
     */
    public function up(): void
    {
        // ── 1. Add running cash balance to users ────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('cash_balance', 15, 2)->default(0)->after('shop_name');
        });

        // ── 2. Daily cash summaries ──────────────────────────────────────────
        Schema::create('daily_cash_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->date('date');
            $table->decimal('cash_in',  15, 2)->default(0);
            $table->decimal('cash_out', 15, 2)->default(0);
            $table->timestamps();

            // One row per user per day — enforced at the DB level
            $table->unique(['user_id', 'date']);

            // Fast lookups: opening balance query filters by user_id + date range
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_cash_summaries');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('cash_balance');
        });
    }
};
