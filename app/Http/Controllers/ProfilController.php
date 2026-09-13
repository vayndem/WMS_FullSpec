<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilRequest;
use App\Http\Requests\UpdatePasswordSendiriRequest;
use App\Models\LogAudit;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Http\Request;
use RuntimeException;

class ProfilController extends Controller
{
    public function __construct(private UserAccountService $akun) {}

    public function show(Request $request)
    {
        $user = $request->user();

        return view('profil.show', [
            'pengguna' => $user,
            'riwayatLogin' => LogAudit::where('auditable_type', User::class)
                ->where('auditable_id', $user->id)
                ->whereIn('event', [UserAccountService::LOGIN, UserAccountService::LOGOUT])
                ->latest('id')->limit(10)->get(),
        ]);
    }

    public function update(UpdateProfilRequest $request)
    {
        $request->user()->update($request->validated());

        return redirect()->route('profil.show')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(UpdatePasswordSendiriRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->akun->gantiPassword($request->user(), $validated['password_lama'], $validated['password']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['password_lama' => $e->getMessage()]);
        }

        return redirect()->route('profil.show')->with('success', 'Password berhasil diganti.');
    }
}
