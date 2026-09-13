<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\LogAudit;
use App\Models\User;
use App\Models\UserRole;
use App\Services\UserAccountService;
use Illuminate\Http\Request;
use RuntimeException;

class UserController extends Controller
{
    public function __construct(private UserAccountService $akun) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        if ($request->ajax()) {
            $query = User::query()->with('role')->select('users.*');

            if ($request->filled('status')) {
                $query->where('is_active', $request->input('status') === 'AKTIF');
            }

            return datatables()->of($query)
                ->addColumn('role_label', fn ($row) => $row->role_name)
                ->addColumn('status_label', fn ($row) => $row->is_active ? 'AKTIF' : 'NONAKTIF')
                ->addColumn('login_terakhir', fn ($row) => $row->last_login_at?->translatedFormat('d M Y H:i') ?? 'Belum pernah')
                ->addColumn('can_deactivate', fn ($row) => $request->user()->can('deactivate', $row) && $row->id !== $request->user()->id)
                ->filterColumn('role_label', function ($query, $keyword) {
                    $query->whereHas('role', fn ($role) => $role->where('name', 'like', "%{$keyword}%"));
                })
                ->make(true);
        }

        return view('user.index', [
            'roles' => UserRole::orderBy('id')->get(),
            'ringkasan' => [
                'total' => User::count(),
                'aktif' => User::where('is_active', true)->count(),
                'nonaktif' => User::where('is_active', false)->count(),
                'belum_pernah_login' => User::whereNull('last_login_at')->count(),
            ],
        ]);
    }

    public function create()
    {
        $this->authorize('create', User::class);

        return view('user.create', ['roles' => UserRole::orderBy('id')->get()]);
    }

    public function store(StoreUserRequest $request)
    {
        $this->akun->buat($request->validated());

        return redirect()->route('user.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return view('user.edit', [
            'pengguna' => $user,
            'roles' => UserRole::orderBy('id')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $this->akun->perbarui($user, $request->validated());
        } catch (RuntimeException $e) {
            return back()->withErrors(['type' => $e->getMessage()])->withInput();
        }

        return redirect()->route('user.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function deactivate(Request $request, User $user)
    {
        $this->authorize('deactivate', $user);

        try {
            $this->akun->nonaktifkan($user, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['user' => $e->getMessage()]);
        }

        return redirect()->route('user.index')->with('success', 'Akun ' . $user->name . ' dinonaktifkan. Sesi aktifnya sudah dicabut.');
    }

    public function reactivate(User $user)
    {
        $this->authorize('deactivate', $user);

        try {
            $this->akun->aktifkanKembali($user);
        } catch (RuntimeException $e) {
            return back()->withErrors(['user' => $e->getMessage()]);
        }

        return redirect()->route('user.index')->with('success', 'Akun ' . $user->name . ' diaktifkan kembali.');
    }

    public function loginAudit(Request $request)
    {
        $this->authorize('viewAny', User::class);

        if ($request->ajax()) {
            $query = LogAudit::query()
                ->with('user')
                ->where('auditable_type', User::class)
                ->whereIn('event', [
                    UserAccountService::LOGIN,
                    UserAccountService::LOGOUT,
                    UserAccountService::LOGIN_GAGAL,
                    UserAccountService::LOGIN_DITOLAK,
                ]);

            return datatables()->of($query)
                ->addColumn('waktu', fn ($row) => $row->created_at->translatedFormat('d M Y H:i:s'))
                ->addColumn('pengguna', fn ($row) => $row->user->name ?? ($row->metadata['email'] ?? 'Tidak dikenal'))
                ->addColumn('email', fn ($row) => $row->metadata['email'] ?? '-')
                ->make(true);
        }

        return view('user.login-audit', [
            'ringkasan' => [
                'login_24_jam' => LogAudit::where('event', UserAccountService::LOGIN)->where('created_at', '>=', now()->subDay())->count(),
                'gagal_24_jam' => LogAudit::whereIn('event', [UserAccountService::LOGIN_GAGAL, UserAccountService::LOGIN_DITOLAK])
                    ->where('created_at', '>=', now()->subDay())->count(),
            ],
        ]);
    }
}
