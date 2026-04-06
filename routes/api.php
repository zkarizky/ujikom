<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ZakatController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum','admin'])->prefix('admin')->group(function(){

    Route::get('/dashboard',[AdminController::class,'dashboard']);

    Route::get('/users',[AdminController::class,'users']);
    Route::delete('/users/{id}',[AdminController::class,'deleteUser']);

    Route::get('/zakat',[AdminController::class,'zakat']);

    Route::post('/gold-price',[AdminController::class,'updateGold']);
});
Route::middleware('auth:sanctum')->group(function(){

    Route::post('/logout',[AuthController::class,'logout']);

    Route::post('/zakat/mal',[ZakatController::class,'zakatMal']);
    Route::post('/zakat/profesi',[ZakatController::class,'zakatProfesi']);
    Route::get('/zakat/history',[ZakatController::class,'history']);
    Route::get('/gold-price', [ZakatController::class, 'goldPrice']);
    Route::get('/dashboard', [ZakatController::class, 'dashboard']);
        Route::post('/zakat', [ZakatController::class, 'store']);
        Route::get('/admin/export', [ZakatController::class, 'exportCSV']);
    });
