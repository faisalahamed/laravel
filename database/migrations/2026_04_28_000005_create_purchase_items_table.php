<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->decimal('buying_price', 18, 2);
            $table->decimal('est_selling_price', 18, 2)->nullable();
            $table->integer('quantity')->default(0);
            $table->string('barcode')->nullable();
            $table->decimal('other_charge', 18, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('product_image')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'category_id', 'purchase_id']);
            $table->index(['shop_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};

