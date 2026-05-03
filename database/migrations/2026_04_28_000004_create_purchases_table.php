<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained()->cascadeOnDelete();
            $table->decimal('total', 18, 2);
            $table->decimal('other_charge', 18, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('buying_memo_url')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();

            $table->index(['shop_id', 'supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};

