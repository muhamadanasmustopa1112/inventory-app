<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductUnitQrController;

Route::get('/', function () {
    return 'Backend Inventory API';
});

Route::get('/qr/product-unit/{productUnit}', [ProductUnitQrController::class, 'show']);


Route::get('/login', function () {
    return response()->json([
        'message' => 'Unauthenticated. Please login via /api/login (Bearer token).'
    ], 401);
})->name('login');


