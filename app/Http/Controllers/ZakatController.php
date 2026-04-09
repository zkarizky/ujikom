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
    private function getGoldPrice()
    {
        return Cache::remember('gold_price', 3600, function () {

            // 🪙 ambil harga emas USD
            $gold = Http::withHeaders([
                'x-access-token' => env('GOLD_API_KEY')
            ])->get('https://www.goldapi.io/api/XAU/USD');

            if (!$gold->successful()) {
                return 1000000;
            }

            $goldUsd = $gold->json()['price_gram_24k'] ?? 0;

            // 💱 kurs USD → IDR
            $rate = Http::get('https://api.exchangerate-api.com/v4/latest/USD');

            if (!$rate->successful()) {
                return 1000000;
            }

            $usdToIdr = $rate->json()['rates']['IDR'] ?? 15000;

            return round($goldUsd * $usdToIdr);
        });
    }
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

        $snapToken = Snap::getSnapToken($params);

        return response()->json([
            'token' => $snapToken
        ]);
    }
    public function exportCsv()
    {
     $year = $request->year ?? date('Y');

    $payments = Payment::with(['user','zakat'])
        ->whereYear('created_at', $year)
        ->get();

    return response()
        ->view('exports.zakat', compact('payments','year'))
        ->header('Content-Type', 'application/vnd.ms-excel')
        ->header('Content-Disposition', 'attachment; filename="zakat-'.$year.'.xls"');
    }
        public function goldPrice()
    {
        $price = Cache::remember('gold_price', 3600, function () {

            // 🪙 ambil harga emas USD
            $gold = Http::withHeaders([
                'x-access-token' => env('GOLD_API_KEY')
            ])->get('https://www.goldapi.io/api/XAU/USD');

            if (!$gold->successful()) {
                return 1000000;
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
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $zakat = ZakatCalculation::where('user_id', $user->id)
            ->latest()
            ->get();
            
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
    public function zakatMal(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile || !$profile->wealth) {
            return response()->json([
                'message' => 'Lengkapi data kekayaan di profil terlebih dahulu.'
            ], 422);
        }

        $request->validate([
            'debt' => 'nullable|numeric' // Form input hutang jatuh tempo
        ]);

        $gold = $this->getGoldPrice();
        $nisab = 85 * $gold;

        $wealth = $profile->wealth;
        $debt = $request->debt ?? 0;
        
        // Kekayaan dikurangi hutang jatuh tempo
        $wealth = $wealth - $debt;
        if ($wealth < 0) {
            $wealth = 0;
        }

        $eligible = $wealth >= $nisab;
        $zakat = $eligible ? $wealth * 0.025 : 0;

        $data = ZakatCalculation::create([
            'user_id' => $user->id,
            'type' => 'mal',
            'income' => $wealth, // Simpan total kekayaan bersih setelah hutang
            'nisab' => $nisab,
            'zakat_amount' => $zakat,
            'is_eligible' => $eligible
        ]);

        return response()->json($data);
    }

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
            'expenses' => 'nullable|numeric' // Form input untuk gaji bersih
        ]);

        $gold = $this->getGoldPrice();

        $nisabTahunan = 85 * $gold;
        $nisabBulanan = $nisabTahunan / 12;

        $income = $profile->income;
        $period = $request->period;

        // Asumsi gaji di profil adalah gaji bulanan.
        // Jika periode "tahunan" yang dipilih, otomatis gaji bulanan dikali 12.
        // (Atau jika memang request Anda "periode bulanan dikali 12", berarti ini menyetahunkan hitungan.)
        // Di sini saya implementasikan: jika setahun, dikali 12.
        if ($period === 'tahunan') {
            $income = $income * 12;
        }

        // Kalau pilih gaji bersih, dikurangi pengeluaran dari form input (tetap form)
        if ($request->salary_type === 'bersih') {
            $income = $income - ($request->expenses ?? 0);
        }
        if ($income < 0) {
            $income = 0;
        }

        $nisab = $period === 'bulanan' ? $nisabBulanan : $nisabTahunan;

        $eligible = $income >= $nisab;
        $zakat = $eligible ? $income * 0.025 : 0;

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
    public function history()
    {
        return ZakatCalculation::where('user_id',auth()->id())
                ->latest()
                ->get();
    }
}
