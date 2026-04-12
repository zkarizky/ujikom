<?php

namespace App\Http\Controllers;

use App\Models\GoldPrice;
use App\Models\ZakatCalculation;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Midtrans\Config;
use Midtrans\Snap;

class ZakatController extends Controller
{
    /**
     * Mengambil harga emas dunia secara realtime melalui API eksternal (GoldAPI.io)
     * Menggunakan cache selama 3600 detik (1 jam) agar tidak membebani limit API bawaan.
     */
    private function getGoldPrice()
    {
        return Cache::remember('gold_price', 3600, function () {

            // 🪙 Minta harga emas (XAU) dalam USD via HTTP Client
            $gold = Http::withHeaders([
                'x-access-token' => env('GOLD_API_KEY')
            ])->get('https://www.goldapi.io/api/XAU/USD');

            // Jika limit habis atau error dari server, gunakan angka konstan darurat (fallback)
            if (!$gold->successful()) {
                return 1000000;
            }

            // Ekstrak nilai harga gram 24 Karat 
            $goldUsd = $gold->json()['price_gram_24k'] ?? 0;

            // 💱 Minta nilai tukar (Kurs) mata uang dari USD ke Rupiah (IDR)
            $rate = Http::get('https://api.exchangerate-api.com/v4/latest/USD');

            if (!$rate->successful()) {
                return 1000000;
            }

            // Ekstrak rate IDR dari respons
            $usdToIdr = $rate->json()['rates']['IDR'] ?? 15000;

            // Mengembalikan nilai emas dirupiahkan yang sudah dibulatkan
            return round($goldUsd * $usdToIdr);
        });
    }

    /**
     * Membayar zakat dengan menggenerate token Midtrans SNAP secara langsung.
     * (Serupa dengan PaymentController, diperuntukkan jika dipanggil dari controller Zakat)
     */
    public function payZakat(Request $request)
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $user = $request->user();
        $amount = (int) $request->amount;

        $params = [
            'transaction_details' => [
                'order_id' => 'ZAKAT-' . time(),
                'gross_amount' => $amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
        ];

        // Buat snap token dan lempar ke Frontend
        $snapToken = Snap::getSnapToken($params);

        return response()->json([
            'token' => $snapToken
        ]);
    }

    /**
     * Mengekspor data pembayaran Zakat menjadi file Excel (.xls) / (.csv)
     * Dapat difilter berdasarkan tahun pembuatan transaksi/record.
     */
    public function exportCsv(Request $request)
    {
        // Ambil tahun dari request, jika tidak ada pakai tahun saat ini
        $year = (int) $request->input('year', date('Y'));

        // Query relasi user dan perhitungan zakat difilter setahun
        $payments = Payment::with(['user','zakat'])
            ->whereYear('created_at', $year)
            ->get();

        // Download view HTML diubah header respons-nya agar dikenali sebagai Excel
        return response()
            ->view('exports.zakat', compact('payments','year'))
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="zakat-'.$year.'.xls"');
    }

    /**
     * Endpoint API (Public) untuk mengambil harga Emas IDR terbaru (Mirip private function diatas)
     * Digunakan murni sebagai endpoint GET untuk dipanggil dari Frontend
     */
    public function goldPrice()
    {
        $price = Cache::remember('gold_price', 3600, function () {

            // 🪙 ambil harga emas USD
            $gold = Http::withHeaders([
                'x-access-token' => env('GOLD_API_KEY')
            ])->get('https://www.goldapi.io/api/XAU/USD');

            if (!$gold->successful()) {
                return 1000000; // Harga emas fallback jika API down
            }

            $goldData = $gold->json();
            $goldUsd = $goldData['price_gram_24k'] ?? 0;

            // 💱 ambil kurs USD → IDR
            $rate = Http::get('https://api.exchangerate-api.com/v4/latest/USD');

            if (!$rate->successful()) {
                return 1000000;
            }

            $rateData = $rate->json();
            $usdToIdr = $rateData['rates']['IDR'] ?? 17000;

            // 🔥 convert ke rupiah
            $priceIdr = $goldUsd * $usdToIdr;

            return round($priceIdr);
        });

        return response()->json([
            'price' => $price
        ]);
    }

    /**
     * Statistik & Ringkasan User Zakat yang dikirimkan ke Dashboard (Halaman utama)
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        // 1. Ambil History perhitungan Zakat Mal maupun Profesi
        $zakat = ZakatCalculation::where('user_id', $user->id)
            ->latest()
            ->get();
            
        // 2. Ambil Rekam jejak Pembayaran/Payment
        $payments = Payment::with('zakat')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'user' => $user,
            'profile' => $user->profile,
            'zakat' => $zakat,
            'payments' => $payments
        ]);
    }

    /**
     * Menghitung Kewajiban Zakat Mal (Harta Tersimpan)
     */
    public function zakatMal(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        // Syarat kalkulasi adalah Profilnya harus memiliki data Total Kekayaan
        if (!$profile || !$profile->wealth) {
            return response()->json([
                'message' => 'Lengkapi data kekayaan di profil terlebih dahulu.'
            ], 422);
        }

        $request->validate([
            'debt' => 'nullable|numeric' // Form input hutang jatuh tempo
        ]);

        $gold = $this->getGoldPrice();
        $nisab = 85 * $gold; // Standar syariat Nisab Zakat Mal = 85 Gram Emas

        $wealth = $profile->wealth;
        $debt = $request->debt ?? 0;
        
        // Kekayaan (Nilai Harta) dikurangi hutang jatuh tempo yang harus dibayar saat ini juga
        $wealth = $wealth - $debt;
        if ($wealth < 0) {
            $wealth = 0;
        }

        // Tentukan apakah memenuhi syarat, lalu hitung Zakatnya (2.5%)
        $eligible = $wealth >= $nisab;
        $zakat = $eligible ? $wealth * 0.025 : 0;

        // Simpan Log Record Hitungannya
        $data = ZakatCalculation::create([
            'user_id' => $user->id,
            'type' => 'mal',
            'income' => $wealth, // Simpan total kekayaan bersih setelah hutang sebagai komparasi utama
            'nisab' => $nisab,
            'zakat_amount' => $zakat,
            'is_eligible' => $eligible
        ]);

        return response()->json($data);
    }

    /**
     * Menghitung Kewajiban Zakat Penghasilan (Profesi)
     */
    public function zakatProfesi(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile || !$profile->income) {
            return response()->json([
                'message' => 'Lengkapi data gaji di profil terlebih dahulu.'
            ], 422);
        }

        $request->validate([
            'period' => 'required|in:bulanan,tahunan',
            'salary_type' => 'required|in:kotor,bersih',
            'expenses' => 'nullable|numeric' // Form pengeluaran hanya jika metode Gaji Bersih
        ]);

        $gold = $this->getGoldPrice();

        // Standar syarat Nisab Profesi (Analog Qiyas Majelis Ulama: 85 Gram Emas setahun)
        $nisabTahunan = 85 * $gold;
        $nisabBulanan = $nisabTahunan / 12;

        $income = $profile->income;  // Pendapatan pokok bulanan dari db profil
        $period = $request->period;

        // Jika periode "tahunan" yang dipilih, otomatis gaji rata2 bulanan disetahunkan (x12)
        if ($period === 'tahunan') {
            $income = $income * 12;
        }

        // Kalau pilih potongan kebutuhan/biaya hidup (gaji bersih) direduksi dari form input
        if ($request->salary_type === 'bersih') {
            $income = $income - ($request->expenses ?? 0);
        }

        // Pastikan tidak minus
        if ($income < 0) {
            $income = 0;
        }

        // Seleksi Nisab pembanding berdasarkan periode perhitungan (perbulan vs pertahun)
        $nisab = $period === 'bulanan' ? $nisabBulanan : $nisabTahunan;

        $eligible = $income >= $nisab;
        $zakat = $eligible ? $income * 0.025 : 0; // Tarif 2.5%

        $data = ZakatCalculation::create([
            'user_id' => $user->id,
            'type' => 'profesi',
            'salary_type' => $request->salary_type,
            'period' => $period,
            'income' => $income,
            'nisab' => $nisab,
            'zakat_amount' => $zakat,
            'is_eligible' => $eligible
        ]);

        return response()->json($data);
    }

    /**
     * Fallback fungsi simpan paksa hitungan independen (Bypassed from specific logics)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'type' => 'required|in:mal,profesi',
            'income' => 'required|numeric',
            'period' => 'required|in:bulanan,tahunan',
            'salary_type' => 'required|in:kotor,bersih',
            'expenses' => 'nullable|numeric'
        ]);

        $goldPrice = $this->getGoldPrice();
        $nisab = 85 * $goldPrice;

        if ($request->type == 'profesi' && $request->period == 'bulanan') {
            $nisab = $nisab / 12;
        }

        $income = $request->income;

        // 🔥 LOGIC BERSIH
        if ($request->salary_type == 'bersih') {
            $income = $income - ($request->expenses ?? 0);
        }

        $isEligible = $income >= $nisab;
        $zakatAmount = $isEligible ? $income * 0.025 : 0;

        $zakat = ZakatCalculation::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'salary_type' => $request->salary_type,
            'period' => $request->period,
            'income' => $request->income,
            'nisab' => $nisab,
            'zakat_amount' => $zakatAmount,
            'is_eligible' => $isEligible,
        ]);

        return response()->json($zakat);
    }

    /**
     * Memuat histori Kalkulasi tanpa history payment.
     */
    public function history()
    {
        return ZakatCalculation::where('user_id',auth()->id())
                ->latest()
                ->get();
    }
}
