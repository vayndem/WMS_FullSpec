<?php

namespace App\Http\Controllers;

use App\Http\Requests\PutuskanPermintaanPersetujuanRequest;
use App\Models\PermintaanPersetujuan;
use App\Services\PersetujuanOperasiService;
use Illuminate\Http\Request;
use RuntimeException;

class PermintaanPersetujuanController extends Controller
{
    public function __construct(private PersetujuanOperasiService $persetujuan) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PermintaanPersetujuan::class);

        $status = $request->input('status', PermintaanPersetujuan::PENDING);

        $permintaan = PermintaanPersetujuan::with(['pemohon', 'pemutus'])
            ->when(
                in_array($status, [PermintaanPersetujuan::PENDING, PermintaanPersetujuan::APPROVED, PermintaanPersetujuan::REJECTED], true),
                fn ($query) => $query->where('status', $status)
            )
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('permintaan_persetujuan.index', [
            'permintaan' => $permintaan,
            'status' => $status,
            'jumlahPending' => PermintaanPersetujuan::pending()->count(),
        ]);
    }

    public function approve(PutuskanPermintaanPersetujuanRequest $request, PermintaanPersetujuan $permintaan)
    {
        $this->authorize('decide', $permintaan);

        try {
            $this->persetujuan->setujui($permintaan, $request->user(), $request->validated('catatan_checker'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['persetujuan' => $e->getMessage()]);
        }

        return back()->with('success', "Permintaan {$permintaan->nomor} disetujui dan dijalankan.");
    }

    public function reject(PutuskanPermintaanPersetujuanRequest $request, PermintaanPersetujuan $permintaan)
    {
        $this->authorize('decide', $permintaan);

        try {
            $this->persetujuan->tolak($permintaan, $request->user(), $request->validated('catatan_checker'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['persetujuan' => $e->getMessage()]);
        }

        return back()->with('success', "Permintaan {$permintaan->nomor} ditolak.");
    }
}
