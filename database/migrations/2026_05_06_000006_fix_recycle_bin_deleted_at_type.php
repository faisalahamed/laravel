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
        DB::statement('ALTER TABLE recycle_bin_entries MODIFY deleted_at DATETIME NOT NULL');
        DB::statement('ALTER TABLE recycle_bin_entries MODIFY restored_at DATETIME NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE recycle_bin_entries MODIFY deleted_at TIMESTAMP NOT NULL');
        DB::statement('ALTER TABLE recycle_bin_entries MODIFY restored_at TIMESTAMP NULL');
    }
};
