<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ZakatCalculation;

class AdminController extends Controller
{
    // dashboard admin
    public function dashboard()
    {
        return response()->json([
            'total_user' => User::count(),
            'total_zakat' => ZakatCalculation::sum('zakat_amount'),
            'total_transaksi' => ZakatCalculation::count(),
        ]);
    }

    // semua user
    public function users()
    {
        return User::all();
    }

    // hapus user
    public function deleteUser($id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message'=>'User dihapus']);
    }

    // semua zakat
    public function zakat()
    {
        return ZakatCalculation::with('user')->latest()->get();
    }

    // update harga emas
    
}
