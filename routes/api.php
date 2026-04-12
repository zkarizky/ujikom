<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\ZakatController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use App\Models\Payment;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum','role:admin'])->prefix('admin')->group(function(){

    Route::get('/dashboard',[AdminController::class,'dashboard']);
    Route::get('/payments',[AdminController::class,'payments']);
    Route::get('/users',[AdminController::class,'users']);
    Route::delete('/users/{id}',[AdminController::class,'deleteUser']);

    Route::get('/zakat',[AdminController::class,'zakat']);

    Route::post('/gold-price',[AdminController::class,'updateGold']);
    Route::get('/export',[ZakatController::class,'exportCsv']);

});
Route::middleware(['auth:sanctum', 'role:user'])->group(function(){

    Route::post('/logout',[AuthController::class,'logout']);
    Route::post('payment/create',[PaymentController::class,'create']);
    Route::post('/zakat/mal',[ZakatController::class,'zakatMal']);
    Route::post('/zakat/profesi',[ZakatController::class,'zakatProfesi']);
    Route::get('/zakat/history',[ZakatController::class,'history']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);
    Route::get('/gold-price', [ZakatController::class, 'goldPrice']);
    Route::get('/dashboard', [ZakatController::class, 'dashboard']);
    Route::post('/zakat', [ZakatController::class, 'store']);
    Route::get('/payment/history',[PaymentController::class,'history']);
    Route::post('/midtrans/transaction', [MidtransController::class, 'createTransaction']);
    Route::post('/payment/success', function(Request $request) {
        $payment = Payment::where('order_id', $request->order_id)->first();

        if ($payment) {
            $payment->update([
                'status' => 'settlement'
            ]);
        }

        return response()->json(['message' => 'updated']);
    });    
});
// Sementara untuk testing — hapus middleware di route
    Route::get('/admin/export',[ZakatController::class,'exportCsv']);

Route::post('/payment/callback', [PaymentController::class,'callback']);
