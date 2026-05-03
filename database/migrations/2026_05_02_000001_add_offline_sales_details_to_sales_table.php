<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('subtotal', 18, 2)->default(0)->after('customer_id');
            $table->decimal('discount', 18, 2)->default(0)->after('subtotal');
            $table->decimal('vat', 18, 2)->default(0)->after('discount');
            $table->string('status')->default('completed')->after('total');
            $table->string('payment_method')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'discount',
                'vat',
                'status',
                'payment_method',
            ]);
        });
    }
};

