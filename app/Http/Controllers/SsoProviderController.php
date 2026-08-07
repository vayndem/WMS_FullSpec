<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AksesApiModel;
use Tymon\JWTAuth\Facades\JWTAuth;

class SsoProviderController extends Controller
{
    public function goToArsip()
    {
        // 1. Ambil data user yang tersimpan di session (hasil login ERP)
        $userData = session('user_data');

        if (!$userData) {
            return redirect()->route('login')->with('error', 'Session habis, silakan login ulang.');
        }

        // 2. Buat "User Bayangan" (Virtual User) dari data session
        // Kita tidak menyimpan ini ke database (tanpa ->save())
        $user = new AksesApiModel();
        $user->id = $userData['id'];
        $user->email = $userData['email'];
        $user->name = $userData['name'];
        $user->type = $userData['type']; // role/jabatan dari ERP

        // 3. Masukkan data tambahan ke dalam Token JWT
        $customClaims = [
            'email' => $user->email,
            'name'  => $user->name,
            'role'  => $user->type,
            'source' => 'inventory-sso'
        ];

        // 4. Buat Token JWT
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);

        // 5. Teruskan ke Arsip QA
        $arsipUrl = "https://qa.erpmulia.online/auth/sso?token=" . $token;

        return redirect()->away($arsipUrl);
    }
}
