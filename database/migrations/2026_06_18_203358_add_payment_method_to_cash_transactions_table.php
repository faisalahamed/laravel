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
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('category');
        });

        // Backfill existing cash transactions linked to sales
        \Illuminate\Support\Facades\DB::table('cash_transactions')
            ->where('transactable_type', 'App\\Models\\Sale')
            ->get()
            ->each(function ($tx) {
                $sale = \Illuminate\Support\Facades\DB::table('sales')->where('id', $tx->transactable_id)->first();
                if ($sale) {
                    $paymentMethod = $sale->payment_method;
                    if (!$paymentMethod && $sale->items) {
                        $items = json_decode($sale->items, true);
                        $paymentMethod = $items['payment_method'] ?? null;
                    }
                    if ($paymentMethod) {
                        \Illuminate\Support\Facades\DB::table('cash_transactions')
                            ->where('id', $tx->id)
                            ->update(['payment_method' => $paymentMethod]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
