<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => [
                'required',
                'email:rfc,dns,spoof',
                'max:100',
                'unique:users,email',   
            ],
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(compact('user','token'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'ktp' => 'nullable|string|max:20',
            'job' => 'nullable|string|max:100',
            'income' => 'nullable|numeric',
            'wealth' => 'nullable|numeric',
        ]);

        // 🔥 kalau belum ada profile → create
        // 🔥 kalau sudah ada → update
        $profile = Profile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'ktp' => $request->ktp,
                'job' => $request->job,
                'income' => $request->income,
                'wealth' => $request->wealth,
            ]
        );

        return response()->json([
            'message' => 'Profile updated',
            'profile' => $profile
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message'=>'Logout berhasil']);
    }
}  