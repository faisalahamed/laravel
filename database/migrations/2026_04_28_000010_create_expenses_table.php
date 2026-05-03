<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('total', 18, 2);
            $table->timestamps();

            $table->index(['shop_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

