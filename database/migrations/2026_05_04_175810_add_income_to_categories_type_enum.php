<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('product', 'expense', 'commission', 'income') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories_type_enum', function (Blueprint $table) {
            //
        });
    }
};
