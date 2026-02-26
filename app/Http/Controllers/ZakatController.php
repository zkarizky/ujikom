<?php

namespace App\Http\Controllers;

use App\Models\GoldPrice;
use App\Models\ZakatCalculation;
use Illuminate\Http\Request;

class ZakatController extends Controller
{
    private function getGoldPrice()
    {
        return GoldPrice::latest()->first()->price_per_gram;
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

    public function history()
    {
        return ZakatCalculation::where('user_id',auth()->id())
                ->latest()
                ->get();
    }
}
