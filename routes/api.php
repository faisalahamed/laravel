<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SignupOtpController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
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
Route::get('/expenses', [ExpenseController::class, 'index']);
Route::post('/expenses', [ExpenseController::class, 'store']);
