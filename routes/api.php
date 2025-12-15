<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\BuyerController;
use App\Http\Controllers\Api\StockInController;
use App\Http\Controllers\Api\StockOutController;
use App\Http\Controllers\Api\StockReportController;
use App\Http\Controllers\Api\ProductUnitController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ActivityLogController;



// Auth
Route::post('/login', [AuthController::class, 'login']);

// route yang butuh token
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/users', [UserController::class, 'index']);  
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // contoh master data
    Route::apiResource('products', ProductController::class);
    Route::apiResource('buyers', BuyerController::class);
    Route::apiResource('warehouses', WarehouseController::class);

    // transaksi (nanti kita isi logic-nya)
    Route::post('/stock-in', [StockInController::class, 'store']);
    Route::get('/stock-in', [StockInController::class, 'index']);
    Route::get('/stock-in/{stockIn}/units', [StockInController::class, 'units']);
    Route::get('/product-units', [ProductUnitController::class, 'index']);


    Route::post('/stock-out', [StockOutController::class, 'store']);
    Route::get('/stock-out', [StockOutController::class, 'index']);
    Route::post('/stock-out/from-units', [StockOutController::class, 'storeFromUnits']);


    // scan QR (nanti)
    Route::post('/scan-qr', [StockOutController::class, 'scanQr']);

    Route::get('/stock-summary', [StockReportController::class, 'summary']);
    Route::get('/stock-out-summary', [StockReportController::class, 'stockOutSummary']);

    Route::get('/reports/sales', [ReportController::class, 'sales']);
    Route::get('/reports/stock-out', [ReportController::class, 'stockOut']);
    Route::get('/reports/stock-in', [ReportController::class, 'stockIn']);     
    Route::get('/reports/stock-balance', [ReportController::class, 'stockBalance']); 
    Route::get('/reports/stock-out/export-units',[StockReportController::class, 'export']);

    Route::get('logs', [ActivityLogController::class, 'index']);



});
