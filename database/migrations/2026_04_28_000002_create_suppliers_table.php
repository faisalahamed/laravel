<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('mobile')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shop_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
