<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\DuePayment;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashTransactionAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_payment_allocates_to_customer_loan_chronologically()
    {
        $user = User::factory()->create([
            'shop_name' => 'Robin Shop',
            'mobile_number' => '01712345678',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Robin',
            'phone' => '01711111111',
            'total_due' => 0.0,
        ]);

        // 1. Create a customer loan of 50 Tk (due created)
        $loanTx = CashTransaction::create([
            'user_id' => $user->id,
            'type' => 'out',
            'amount' => 50.0,
            'category' => 'customer_loan',
            'transactable_type' => Customer::class,
            'transactable_id' => $customer->id,
            'date_time' => now()->subMinutes(10),
        ]);

        // Manually increment customer's total_due (mirroring CashTransactionController store)
        $customer->increment('total_due', 50.0);

        $this->assertEquals(50.0, $customer->fresh()->total_due);

        // 2. Create a customer payment of 450 Tk (450 in)
        $response = $this->actingAs($user)->postJson('/api/cash-transactions', [
            'type' => 'in',
            'amount' => 450.0,
            'category' => 'customer_payment',
            'transactable_type' => Customer::class,
            'transactable_id' => $customer->id,
            'date_time' => now(),
        ]);

        $response->assertStatus(201);
        
        $paymentTxId = $response->json('id');

        // Total due should be -400.0 (since 50 - 450 = -400)
        $this->assertEquals(-400.0, $customer->fresh()->total_due);

        // There should be a DuePayment created linking the payment to the loan for 50 Tk
        $duePayments = DuePayment::where('cash_transaction_id', $paymentTxId)->get();
        $this->assertCount(1, $duePayments);
        
        $duePayment = $duePayments->first();
        $this->assertEquals(50.0, $duePayment->amount);
        $this->assertEquals(CashTransaction::class, $duePayment->payable_type);
        $this->assertEquals($loanTx->id, $duePayment->payable_id);
    }

    public function test_customer_payment_allocates_to_both_sale_and_loan_chronologically()
    {
        $user = User::factory()->create([
            'shop_name' => 'Robin Shop',
            'mobile_number' => '01712345678',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Robin',
            'phone' => '01711111111',
            'total_due' => 0.0,
        ]);

        // Create Sale first (100 Tk due)
        $sale = Sale::create([
            'user_id' => $user->id,
            'receipt_no' => 'REC-001',
            'customer_id' => $customer->id,
            'total_amount' => 100.0,
            'paid_amount' => 0.0,
            'due_amount' => 100.0,
            'payment_status' => 'due',
            'date_time' => now()->subMinutes(10),
        ]);

        // Create Customer Loan second (50 Tk due)
        $loanTx = CashTransaction::create([
            'user_id' => $user->id,
            'type' => 'out',
            'amount' => 50.0,
            'category' => 'customer_loan',
            'transactable_type' => Customer::class,
            'transactable_id' => $customer->id,
            'date_time' => now()->subMinutes(5),
        ]);

        // Customer total due is 150 Tk
        $customer->increment('total_due', 100.0);
        $customer->increment('total_due', 50.0);

        // Receive payment of 120 Tk
        $response = $this->actingAs($user)->postJson('/api/cash-transactions', [
            'type' => 'in',
            'amount' => 120.0,
            'category' => 'customer_payment',
            'transactable_type' => Customer::class,
            'transactable_id' => $customer->id,
            'date_time' => now(),
        ]);

        $response->assertStatus(201);
        $paymentTxId = $response->json('id');

        // DuePayments should pay 100 Tk off Sale and 20 Tk off Loan
        $duePayments = DuePayment::where('cash_transaction_id', $paymentTxId)->orderBy('amount', 'desc')->get();
        $this->assertCount(2, $duePayments);

        $this->assertEquals(Sale::class, $duePayments[0]->payable_type);
        $this->assertEquals($sale->id, $duePayments[0]->payable_id);
        $this->assertEquals(100.0, $duePayments[0]->amount);

        $this->assertEquals(CashTransaction::class, $duePayments[1]->payable_type);
        $this->assertEquals($loanTx->id, $duePayments[1]->payable_id);
        $this->assertEquals(20.0, $duePayments[1]->amount);

        // Check Sale is paid
        $this->assertEquals(0.0, $sale->fresh()->due_amount);
        $this->assertEquals('paid', $sale->fresh()->payment_status);

        // Deleting the payment should restore Sale due_amount and delete the due payments
        $deleteResponse = $this->actingAs($user)->deleteJson("/api/cash-transactions/{$paymentTxId}");
        $deleteResponse->assertStatus(200);

        $this->assertEquals(100.0, $sale->fresh()->due_amount);
        $this->assertEquals('due', $sale->fresh()->payment_status);
        $this->assertCount(0, DuePayment::where('cash_transaction_id', $paymentTxId)->get());
    }

    public function test_supplier_payment_allocates_to_supplier_loan_chronologically()
    {
        $user = User::factory()->create([
            'shop_name' => 'Robin Shop',
            'mobile_number' => '01712345678',
        ]);

        $supplier = Supplier::create([
            'user_id' => $user->id,
            'name' => 'Supplier A',
            'phone' => '01722222222',
            'total_due' => 0.0,
        ]);

        // 1. Create a supplier loan of 80 Tk (due created, we owe supplier 80 Tk)
        $loanTx = CashTransaction::create([
            'user_id' => $user->id,
            'type' => 'in',
            'amount' => 80.0,
            'category' => 'supplier_loan',
            'transactable_type' => Supplier::class,
            'transactable_id' => $supplier->id,
            'date_time' => now()->subMinutes(10),
        ]);

        // Manually increment supplier's total_due (mirroring CashTransactionController store)
        $supplier->increment('total_due', 80.0);

        $this->assertEquals(80.0, $supplier->fresh()->total_due);

        // 2. Create a supplier payment of 100 Tk (100 out)
        $response = $this->actingAs($user)->postJson('/api/cash-transactions', [
            'type' => 'out',
            'amount' => 100.0,
            'category' => 'purchase',
            'transactable_type' => Supplier::class,
            'transactable_id' => $supplier->id,
            'date_time' => now(),
        ]);

        $response->assertStatus(201);
        
        $paymentTxId = $response->json('id');

        // Total due should be -20.0 (since we paid 20 Tk extra, so we have 20 Tk advance/credit)
        $this->assertEquals(-20.0, $supplier->fresh()->total_due);

        // There should be a DuePayment created linking the payment to the loan for 80 Tk
        $duePayments = DuePayment::where('cash_transaction_id', $paymentTxId)->get();
        $this->assertCount(1, $duePayments);
        
        $duePayment = $duePayments->first();
        $this->assertEquals(80.0, $duePayment->amount);
        $this->assertEquals(CashTransaction::class, $duePayment->payable_type);
        $this->assertEquals($loanTx->id, $duePayment->payable_id);
    }
}
