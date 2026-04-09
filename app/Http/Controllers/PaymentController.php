<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;
use App\Models\Payment;
class PaymentController extends Controller
{
    public function create(Request $request)
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $order_id = 'ZAKAT-' . uniqid();

        $params = [
            'transaction_details' => [
                'order_id' => $order_id,
                'gross_amount' => (int)$request->amount,
            ],
            'customer_details' => [
                'first_name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ],
        ];

        $snapToken = Snap::getSnapToken($params);
         // 🔥 SIMPAN KE DB
        $payment = Payment::create([
            'user_id' => auth()->id(),
            'zakat_id' => $request->zakat_id,
            'order_id' => $order_id,
            'amount' => $request->amount,
            'snap_token' => $snapToken,
            'transaction_status' => 'pending'
        ]);
        return response()->json([
            'token' => $snapToken
        ]);
    }
    public function callback(Request $request)
    {
        $serverKey = env('MIDTRANS_SERVER_KEY');

        $hash = hash("sha512",
            $request->order_id .
            $request->status_code .
            $request->gross_amount .
            $serverKey
        );

        if ($hash !== $request->signature_key) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $payment = Payment::where('order_id', $request->order_id)->first();

        if (!$payment) return;

        $payment->update([
            'transaction_status' => $request->transaction_status,
            'payment_type' => $request->payment_type
        ]);

        return response()->json(['message' => 'OK']);
    }

    public function history()
    {
        return Payment::where('user_id', auth()->id())->get();

        return response()->json($payments);
    }   
}