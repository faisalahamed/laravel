<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\OwnerTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\ExpenseCategoryController;

// ── Public Auth Routes (no token required) ───────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Public endpoint to run migrations programmatically (e.g., during deployments)
Route::get('/run-migrations', function (\Illuminate\Http\Request $request) {
    $token = $request->query('token') ?? $request->header('X-Migration-Token');
    $secret = env('MIGRATION_SECRET');
    if ($secret && $token !== $secret) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        return response()->json([
            'message' => 'Migrations run successfully',
            'output' => $output
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Migration failed',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Public endpoint to drop all tables and re-run migrations
Route::get('/migrate-fresh', function (\Illuminate\Http\Request $request) {
    $token = $request->query('token') ?? $request->header('X-Migration-Token');
    $secret = env('MIGRATION_SECRET');
    if ($secret && $token !== $secret) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        return response()->json([
            'message' => 'Database refreshed successfully',
            'output' => $output
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Database refresh failed',
            'error' => $e->getMessage()
        ], 500);
    }
});

// Public endpoint to recalculate user cash balances and summaries
Route::get('/recalculate-balances', function (\Illuminate\Http\Request $request) {
    $token = $request->query('token') ?? $request->header('X-Migration-Token');
    $secret = env('MIGRATION_SECRET');
    if ($secret && $token !== $secret) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    try {
        $seeder = new \Database\Seeders\SeedCashBalanceSeeder();
        $seeder->run();
        return response()->json([
            'message' => 'Balances and daily summaries recalculated successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Recalculation failed',
            'error' => $e->getMessage()
        ], 500);
    }
});

// ── Protected Routes (Bearer token required) ─────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Dashboard Summary API
    Route::get('/summary', [DashboardController::class, 'getSummary']);

    // Resource CRUD routes
    Route::apiResource('/customers',          CustomerController::class);
    Route::apiResource('/suppliers',          SupplierController::class);
    Route::apiResource('/employees',          EmployeeController::class);
    Route::apiResource('/sales',              SaleController::class);
    Route::apiResource('/purchases',          PurchaseController::class);
    Route::apiResource('/expenses',           ExpenseController::class);
    Route::apiResource('/owner-transactions', OwnerTransactionController::class);
    Route::apiResource('/cash-transactions',  CashTransactionController::class);
    Route::apiResource('/expense-categories', ExpenseCategoryController::class);
});
