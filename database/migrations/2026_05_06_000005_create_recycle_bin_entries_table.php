<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recycle_bin_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_id')->constrained()->cascadeOnDelete();
            $table->string('table_name');
            $table->uuid('record_id');
            $table->string('display_title');
            $table->string('display_subtitle')->nullable();
            $table->json('deleted_data');
            $table->json('related_data')->nullable();
            $table->foreignUuid('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('deleted_at');
            $table->dateTime('restored_at')->nullable();
            $table->string('restore_status')->default('deleted');
            $table->text('restore_block_reason')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'restore_status', 'deleted_at']);
            $table->index(['table_name', 'record_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recycle_bin_entries');
    }
};
