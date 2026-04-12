<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Str;

class MidtransController extends Controller
{
    

    public function createTransaction(Request $request)
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = false;

        $orderId = 'ORDER-' . time();

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $request->amount,
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        // 🔥 SIMPAN SETELAH SNAP TOKEN ADA
        $payment = Payment::create([
            'user_id' => auth()->id(),
            'order_id' => $orderId,
            'amount' => $request->amount,
            'snap_token' => $snapToken,
            'transaction_status' => 'pending',
        ]);

        return response()->json([
            'snap_token' => $snapToken
        ]);
    }
}
