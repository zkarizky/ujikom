<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ZakatCalculation;
use App\Models\Payment;

class AdminController extends Controller
{
    // dashboard admin
    public function dashboard()
    {
        return response()->json([
            'total_user' => User::count(),
            'total_zakat' => Payment::where('transaction_status', 'settlement')->sum('amount'),
            'total_transaksi' => Payment::count(),

            'total_success'=>Payment::where('transaction_status','settlement')->count(),
            'total_pending'=>Payment::where('transaction_status','pending')->count(),
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
    public function payments()
    {
        return Payment::with('user', 'zakat')
            ->latest()
            ->get();
    }
    // update harga emas
    
}
