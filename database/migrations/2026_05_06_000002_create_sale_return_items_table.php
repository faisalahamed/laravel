<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignUuid('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('purchase_items')->cascadeOnDelete();
            $table->string('product_name');
            $table->decimal('sale_price', 18, 2)->default(0);
            $table->unsignedInteger('quantity')->default(0);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'created_at']);
            $table->index('return_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
