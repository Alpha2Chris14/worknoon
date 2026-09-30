<?php

use App\Http\Controllers\RefundController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/health', fn() => ['ok' => true, 'llm_configured' => (bool) config('services.gemini.key')]);
Route::post('/refund-requests', [RefundController::class, 'store'])->middleware('throttle:30,1');
Route::get('/refund-requests', [RefundController::class, 'index']);
Route::get('/orders', [RefundController::class, 'orders']);
