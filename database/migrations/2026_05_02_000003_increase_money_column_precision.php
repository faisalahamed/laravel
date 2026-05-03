<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->moneyColumns() as $table => $columns) {
            foreach ($columns as $column => $definition) {
                DB::statement(
                    "ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}",
                );
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $previousDefinitions = [
            'purchases' => [
                'total' => 'DECIMAL(10, 2) NOT NULL',
                'other_charge' => 'DECIMAL(10, 2) NOT NULL DEFAULT 0',
            ],
            'purchase_items' => [
                'buying_price' => 'DECIMAL(10, 2) NOT NULL',
                'est_selling_price' => 'DECIMAL(10, 2) NULL',
                'other_charge' => 'DECIMAL(10, 2) NOT NULL DEFAULT 0',
            ],
            'purchase_payments' => [
                'payments' => 'DECIMAL(10, 2) NOT NULL',
            ],
            'sales' => [
                'subtotal' => 'DECIMAL(10, 2) NOT NULL DEFAULT 0',
                'discount' => 'DECIMAL(10, 2) NOT NULL DEFAULT 0',
                'vat' => 'DECIMAL(10, 2) NOT NULL DEFAULT 0',
                'total' => 'DECIMAL(10, 2) NOT NULL',
            ],
            'sale_items' => [
                'buy_price' => 'DECIMAL(10, 2) NOT NULL',
                'sale_price' => 'DECIMAL(10, 2) NOT NULL',
                'price' => 'DECIMAL(10, 2) NOT NULL',
            ],
            'customer_payments' => [
                'payments' => 'DECIMAL(10, 2) NOT NULL',
            ],
            'expenses' => [
                'amount' => 'DECIMAL(10, 2) NOT NULL DEFAULT 0',
                'total' => 'DECIMAL(10, 2) NOT NULL',
            ],
            'cash_transactions' => [
                'amount' => 'DECIMAL(12, 2) NOT NULL',
            ],
        ];

        foreach ($previousDefinitions as $table => $columns) {
            foreach ($columns as $column => $definition) {
                DB::statement(
                    "ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}",
                );
            }
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function moneyColumns(): array
    {
        return [
            'purchases' => [
                'total' => 'DECIMAL(18, 2) NOT NULL',
                'other_charge' => 'DECIMAL(18, 2) NOT NULL DEFAULT 0',
            ],
            'purchase_items' => [
                'buying_price' => 'DECIMAL(18, 2) NOT NULL',
                'est_selling_price' => 'DECIMAL(18, 2) NULL',
                'other_charge' => 'DECIMAL(18, 2) NOT NULL DEFAULT 0',
            ],
            'purchase_payments' => [
                'payments' => 'DECIMAL(18, 2) NOT NULL',
            ],
            'sales' => [
                'subtotal' => 'DECIMAL(18, 2) NOT NULL DEFAULT 0',
                'discount' => 'DECIMAL(18, 2) NOT NULL DEFAULT 0',
                'vat' => 'DECIMAL(18, 2) NOT NULL DEFAULT 0',
                'total' => 'DECIMAL(18, 2) NOT NULL',
            ],
            'sale_items' => [
                'buy_price' => 'DECIMAL(18, 2) NOT NULL',
                'sale_price' => 'DECIMAL(18, 2) NOT NULL',
                'price' => 'DECIMAL(18, 2) NOT NULL',
            ],
            'customer_payments' => [
                'payments' => 'DECIMAL(18, 2) NOT NULL',
            ],
            'expenses' => [
                'amount' => 'DECIMAL(18, 2) NOT NULL DEFAULT 0',
                'total' => 'DECIMAL(18, 2) NOT NULL',
            ],
            'cash_transactions' => [
                'amount' => 'DECIMAL(18, 2) NOT NULL',
            ],
        ];
    }
};
