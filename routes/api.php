<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ZakatController;

Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);

Route::middleware('auth:sanctum')->group(function(){

    Route::post('/logout',[AuthController::class,'logout']);

    Route::post('/zakat/mal',[ZakatController::class,'zakatMal']);
    Route::post('/zakat/profesi',[ZakatController::class,'zakatProfesi']);
    Route::get('/zakat/history',[ZakatController::class,'history']);

});