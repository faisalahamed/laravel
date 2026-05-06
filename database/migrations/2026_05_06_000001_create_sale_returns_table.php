<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('restocking_fee', 18, 2)->default(0);
            $table->decimal('refund_total', 18, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'created_at']);
            $table->index('sale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
