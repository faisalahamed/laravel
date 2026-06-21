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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('payment_status');
        });

        // Copy existing payment methods from items JSON if they exist
        \Illuminate\Support\Facades\DB::table('sales')->get()->each(function ($sale) {
            $items = json_decode($sale->items, true);
            if (isset($items['payment_method'])) {
                \Illuminate\Support\Facades\DB::table('sales')->where('id', $sale->id)->update([
                    'payment_method' => $items['payment_method']
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
