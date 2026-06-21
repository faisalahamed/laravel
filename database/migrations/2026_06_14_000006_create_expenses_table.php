<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('category');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->text('reason')->nullable();
            $table->string('attachment_path')->nullable();
            $table->date('date');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
