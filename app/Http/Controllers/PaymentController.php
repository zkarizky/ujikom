<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Midtrans\Config;
use Midtrans\Snap;

class PaymentController extends Controller
{
    // 🔥 CREATE TRANSACTION
    public function create(Request $request)
    {
        // config midtrans
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // order id unik
        $order_id = 'ZKT-' . time() . rand(100,999);

        // parameter midtrans
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

        // ambil snap token
        $snapToken = Snap::getSnapToken($params);

        // simpan ke DB
        $payment = Payment::create([
            'user_id' => auth()->id(),
            'zakat_id' => $request->zakat_id,
            'order_id' => $order_id,
            'amount' => $request->amount,
            'snap_token' => $snapToken,
            'transaction_status' => 'pending',
        ]);

        return response()->json([
            'snap_token' => $snapToken
        ]);
    }

    // 🔥 CALLBACK MIDTRANS (WAJIB DI LUAR AUTH)
    public function callback(Request $request)
    {
        \Log::info("MIDTRANS CALLBACK", $request->all());

        Config::$serverKey = env('MIDTRANS_SERVER_KEY');

        // validasi signature (biar aman)
        $signature = hash('sha512',
            $request->order_id .
            $request->status_code .
            $request->gross_amount .
            Config::$serverKey
        );

        if ($signature !== $request->signature_key) {
            \Log::error("INVALID SIGNATURE");
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $order_id = $request->order_id;

        $payment = Payment::where('order_id', $order_id)->first();

        // kalau ga ketemu (biasanya test notif)
        if (!$payment) {
            \Log::warning("PAYMENT NOT FOUND: " . $order_id);
            return response()->json(['message' => 'ignored']);
        }

        // update status
        $payment->update([
            'transaction_status' => $request->transaction_status,
            'payment_type' => $request->payment_type,
        ]);

        \Log::info("PAYMENT UPDATED", [
            'order_id' => $order_id,
            'status' => $request->transaction_status
        ]);

        return response()->json(['message' => 'OK']);
    }

    // 🔥 HISTORY PAYMENT USER
    public function history()
    {
        $payments = Payment::with(['user','zakat'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return response()->json($payments);
    }
}