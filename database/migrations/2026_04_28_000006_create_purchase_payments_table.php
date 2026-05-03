<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('purchase_id')->constrained()->cascadeOnDelete();
            $table->decimal('payments', 18, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'purchase_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};

