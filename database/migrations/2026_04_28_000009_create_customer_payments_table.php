<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->constrained('sales')->cascadeOnDelete();
            $table->decimal('payments', 18, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'customer_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};

