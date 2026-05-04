<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE cash_transactions MODIFY COLUMN type ENUM('sale', 'customer_payment', 'purchase_payment', 'expense', 'owner_given', 'owner_taken', 'income') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE cash_transactions MODIFY COLUMN type ENUM('sale', 'customer_payment', 'purchase_payment', 'expense', 'owner_given', 'owner_taken') NOT NULL");
    }
};
