<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;
use App\Models\Payment;

class PaymentController extends Controller
{
    /**
     * Membuat transaksi (order) pembayaran baru ke payment gateway Midtrans
     */
    public function create(Request $request)
    {
        // Konfigurasi Kredensial Midtrans
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = false; // Gunakan mode sandbox/testing
        Config::$isSanitized = true;   // Bersihkan input sebelum diproses Midtrans
        Config::$is3ds = true;         // Gunakan protokol 3D Secure untuk keamanan CC

        // Buat ID pesanan unik menggunakan prefiks 'ZAKAT-' digabung uniqid random
        $order_id = 'ZAKAT-' . uniqid();

        // Parameter dasar yang wajib dikirim ke API Snap Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $order_id,
                'gross_amount' => (int)$request->amount, // Nominal bayar
            ],
            'customer_details' => [
                'first_name' => auth()->user()->name,    // Identitas pembayar
                'email' => auth()->user()->email,
            ],
        ];

        // Memanggil layanan Midtrans untuk mendapatkan Snap Token (kunci popup Midtrans)
        $snapToken = Snap::getSnapToken($params);

        // 🔥 SIMPAN KE DB: Simpan histori tagihan berstatus 'pending' (menunggu bayar)
        $payment = Payment::create([
            'user_id' => auth()->id(),
            'zakat_id' => $request->zakat_id,
            'order_id' => $order_id,
            'amount' => $request->amount,
            'snap_token' => $snapToken,
            'transaction_status' => 'pending' // Status standar awal adalah pending
        ]);

        // Mengembalikan Snap Token berbentuk JSON untuk dipakai oleh Frontend
        return response()->json([
            'token' => $snapToken
        ]);
    }

    /**
     * Menangani respons otomatis (Webhook / Callback) dari server Midtrans
     * Fungsi ini dipanggil secara otomatis oleh Midtrans saat ada perubahan status bayar.
     */
 public function callback(Request $request)
{
    \Log::info("MIDTRANS CALLBACK", $request->all());

    $orderId = $request->order_id;
    $status = $request->transaction_status;
    $paymentType = $request->payment_type;

    $payment = Payment::where('order_id', $orderId)->first();

    if (!$payment) {
        \Log::error("PAYMENT NOT FOUND: " . $orderId);
        return response()->json(['message' => 'Payment not found'], 404);
    }

    $payment->update([
        'transaction_status' => $status,
        'payment_type' => $paymentType
    ]);

    \Log::info("PAYMENT UPDATED", [
        'order_id' => $orderId,
        'status' => $status
    ]);

    return response()->json(['message' => 'OK']);
}
    /**
     * Memuat daftar transaksi milik admin atau user yang berstatus lunas (settlement)
     */
    public function history()
    {
        // Hanya ambil tagihan pembayaran yang transaksinya sudah berhasil lunas
        $payments = Payment::where('transaction_status', 'settlement')->get();
        
        return response()->json($payments);
    }   
}