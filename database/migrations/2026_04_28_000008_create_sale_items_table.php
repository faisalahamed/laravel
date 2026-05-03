<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('order_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('purchase_items')->cascadeOnDelete();
            $table->decimal('buy_price', 18, 2);
            $table->decimal('sale_price', 18, 2);
            $table->integer('quantity')->default(0);
            $table->decimal('price', 18, 2);
            $table->timestamps();

            $table->index(['shop_id', 'order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};

