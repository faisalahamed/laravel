<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('due_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_transaction_id')->constrained('cash_transactions')->onDelete('cascade');
            $table->morphs('payable'); // polymorphic connection to Sale or Purchase
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('due_payments');
    }
};
