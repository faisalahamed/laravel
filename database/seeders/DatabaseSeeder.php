<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Employee;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\OwnerTransaction;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default User
        User::factory()->create([
            'name' => 'Tally Shop Owner',
            'email' => 'owner@tallyshop.com',
            'password' => bcrypt('password'),
        ]);

        // Seed Customers
        $customerBabu = Customer::create([
            'name' => 'babu',
            'phone' => '১২৩৪৫৬৭৮৯১১',
            'address' => 'Dhaka, Bangladesh',
            'total_due' => 120.00,
            'status' => 'active',
        ]);

        Customer::create([
            'name' => 'Rahim',
            'phone' => '01711223344',
            'address' => 'Chittagong',
            'total_due' => 0.00,
            'status' => 'active',
        ]);

        // Seed Suppliers
        $supplierMula = Supplier::create([
            'name' => 'Mula Shop Bangladesh',
            'phone' => '01911223344',
            'address' => 'Sylhet',
            'total_due' => 1000.00,
            'status' => 'active',
        ]);

        Supplier::create([
            'name' => 'General Wholesaler',
            'phone' => '01511223344',
            'address' => 'Dhaka',
            'total_due' => 0.00,
            'status' => 'active',
        ]);

        // Seed Employees
        $employeeForid = Employee::create([
            'name' => 'Forid',
            'phone' => '012345678901',
            'designation' => 'Salesperson',
            'salary' => 15000.00,
            'status' => 'active',
        ]);

        Employee::create([
            'name' => 'Korim',
            'phone' => '01888888888',
            'designation' => 'Manager',
            'salary' => 25000.00,
            'status' => 'active',
        ]);

        // Seed Sales
        Sale::create([
            'receipt_no' => '8391276E',
            'customer_id' => $customerBabu->id,
            'total_amount' => 120.00,
            'paid_amount' => 0.00,
            'due_amount' => 120.00,
            'payment_status' => 'due',
            'date_time' => Carbon::create(2026, 6, 14, 2, 49, 0),
            'items' => [
                ['name' => '১রকম পণ্য', 'price' => 120, 'quantity' => 1]
            ],
        ]);

        // Seed Purchases
        Purchase::create([
            'receipt_no' => 'D4F5EB31',
            'supplier_id' => $supplierMula->id,
            'total_amount' => 1000.00,
            'paid_amount' => 1000.00,
            'due_amount' => 0.00,
            'payment_status' => 'paid',
            'date_time' => Carbon::create(2026, 6, 10, 18, 12, 0),
            'items' => [
                ['name' => '১ধরনের পণ্য', 'price' => 1000, 'quantity' => 1]
            ],
        ]);

        // Seed Expenses
        Expense::create([
            'category' => 'বেতন',
            'employee_id' => $employeeForid->id,
            'amount' => 200.00,
            'reason' => 'Forid salary partial',
            'date' => Carbon::create(2026, 6, 14),
        ]);

        Expense::create([
            'category' => 'অন্যান্য',
            'amount' => 100.00,
            'reason' => 'Daily tea and snacks',
            'date' => Carbon::create(2026, 6, 14),
        ]);

        // Seed Owner Transactions
        OwnerTransaction::create([
            'type' => 'give',
            'amount' => 5000.00,
            'description' => 'Initial capital injection',
            'date_time' => Carbon::create(2026, 6, 13, 10, 0, 0),
        ]);

        OwnerTransaction::create([
            'type' => 'take',
            'amount' => 1000.00,
            'description' => 'Personal expense withdrawal',
            'date_time' => Carbon::create(2026, 6, 14, 12, 0, 0),
        ]);
    }
}
