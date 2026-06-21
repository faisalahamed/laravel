<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->enum('type', ['in', 'out']); // in = Cash In, out = Cash Out
            $table->decimal('amount', 15, 2);
            $table->string('category'); // sale, purchase, expense, owner_give, owner_take, customer_advance, etc.
            $table->string('description')->nullable();
            $table->nullableMorphs('transactable'); // polymorphic connection to Sale, Purchase, Expense, etc.
            $table->timestamp('date_time')->useCurrent();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
