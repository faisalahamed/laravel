<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['product', 'expense', 'commission']);
            $table->string('image_url')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
