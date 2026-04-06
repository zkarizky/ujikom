<?php

namespace App\Http\Controllers;

use App\Models\GoldPrice;
use App\Models\ZakatCalculation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;


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
    public function exportCSV()
{
    $data = ZakatCalculation::with('user')->get();

    $filename = "zakat.csv";
    $headers = [
        "Content-Type" => "text/csv",
        "Content-Disposition" => "attachment; filename=$filename",
    ];

    $callback = function () use ($data) {
        $file = fopen('php://output', 'w');

        // header
        fputcsv($file, [
            'Nama',
            'Email',
            'Jenis',
            'Periode',
            'Penghasilan',
            'Zakat',
            'Status'
        ]);

        foreach ($data as $row) {
            fputcsv($file, [
                $row->user->name,
                $row->user->email,
                $row->type,
                $row->period,
                $row->income,
                $row->zakat_amount,
                $row->is_eligible ? 'Wajib' : 'Tidak'
            ]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
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
            $usdToIdr = $rateData['rates']['IDR'] ?? 15000;

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

        return response()->json([
            'user' => $user,
            'zakat' => $zakat
        ]);
    }
    public function zakatMal(Request $request)
    {
        $gold = $this->getGoldPrice();
        $nisab = 85 * $gold;

        $income = $request->income;

        $eligible = $income >= $nisab;
        $zakat = $eligible ? $income * 0.025 : 0;

        $data = ZakatCalculation::create([
            'user_id' => auth()->id(),
            'type' => 'mal',
            'income' => $income,
            'nisab' => $nisab,
            'zakat_amount' => $zakat,
            'is_eligible' => $eligible
        ]);

        return response()->json($data);
    }

    public function zakatProfesi(Request $request)
    {
        $gold = $this->getGoldPrice();

        $nisabTahunan = 85 * $gold;
        $nisabBulanan = $nisabTahunan / 12;

        $income = $request->income;
        $period = $request->period;

        $nisab = $period == 'bulanan' ? $nisabBulanan : $nisabTahunan;

        $eligible = $income >= $nisab;
        $zakat = $eligible ? $income * 0.025 : 0;

        $data = ZakatCalculation::create([
            'user_id' => auth()->id(),
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
