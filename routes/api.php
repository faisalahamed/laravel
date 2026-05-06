<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SignupOtpController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\OwnerTransactionController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RecycleBinController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return ['status' => 'ok'];
});

Route::get('/db-test', function () {
    return DB::connection()->getDatabaseName();
});

Route::post('/auth/login', [LoginController::class, 'store']);
Route::post('/auth/signup/send-otp', [SignupOtpController::class, 'store']);
Route::post('/auth/signup/verify-otp', [SignupOtpController::class, 'verify']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/categories', [CategoryController::class, 'store']);
Route::get('/suppliers', [SupplierController::class, 'index']);
Route::post('/suppliers', [SupplierController::class, 'store']);
Route::get('/purchases', [PurchaseController::class, 'index']);
Route::post('/purchases', [PurchaseController::class, 'store']);
Route::get('/customers', [CustomerController::class, 'index']);
Route::post('/customers', [CustomerController::class, 'store']);
Route::get('/sales', [SaleController::class, 'index']);
Route::post('/sales', [SaleController::class, 'store']);
Route::get('/sales/returns', [SaleReturnController::class, 'index']);
Route::post('/sales/returns', [SaleReturnController::class, 'store']);
Route::get('/expenses', [ExpenseController::class, 'index']);
Route::post('/expenses', [ExpenseController::class, 'store']);
Route::get('/incomes', [IncomeController::class, 'index']);
Route::post('/incomes', [IncomeController::class, 'store']);
Route::get('/owner-transactions', [OwnerTransactionController::class, 'index']);
Route::post('/owner-transactions', [OwnerTransactionController::class, 'store']);
Route::get('/recycle-bin', [RecycleBinController::class, 'index']);
Route::post('/recycle-bin', [RecycleBinController::class, 'store']);
Route::get('/notes', [NoteController::class, 'index']);
Route::post('/notes', [NoteController::class, 'store']);
