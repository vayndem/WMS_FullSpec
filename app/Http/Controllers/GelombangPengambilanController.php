<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGelombangPengambilanRequest;
use App\Models\GelombangPengambilan;
use App\Models\Gudang;
use App\Models\User;
use App\Services\GelombangPengambilanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class GelombangPengambilanController extends Controller
{
    public function __construct(private GelombangPengambilanService $gelombang) {}

    public function index(Request $request)
    {
        abort_unless(Gate::allows('operateWarehouse'), 403);

        $gudangIds = $request->user()->accessibleGudangIds();
        $gudangs = Gudang::whereIn('id', $gudangIds)->where('aktif', true)->orderBy('nama')->get();
        $dipilih = (int) ($request->input('gudang_id') ?: $gudangs->first()->id ?? 0);

        abort_unless($dipilih === 0 || in_array($dipilih, $gudangIds, true), 403);

        return view('wms_control.gelombang', [
            'gudangs' => $gudangs,
            'gudangDipilih' => $dipilih,
            'kandidat' => $dipilih ? $this->gelombang->kandidat($dipilih) : collect(),
            'gelombang' => GelombangPengambilan::with('gudang', 'petugas')
                ->withCount('pesanan')
                ->whereIn('gudang_id', $gudangIds)
                ->latest('id')->limit(25)->get(),
            'petugas' => User::aktif()->berperan([User::ROLE_WAREHOUSE, User::ROLE_PRODUCTION])->orderBy('name')->get(),
            'strategi' => GelombangPengambilan::STRATEGI,
        ]);
    }

    public function store(StoreGelombangPengambilanRequest $request)
    {
        $data = $request->validated();

        try {
            $gelombang = $this->gelombang->buat(
                (int) $data['gudang_id'],
                array_map('intval', $data['pick_ids']),
                $data['strategi'],
                $data['catatan'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['gelombang' => $e->getMessage()]);
        }

        return redirect()->route('gelombang-pengambilan.index', ['gudang_id' => $data['gudang_id']])
            ->with('success', "Gelombang {$gelombang->nomor} dibuat dengan {$gelombang->pesanan()->count()} perintah pengambilan.");
    }

    public function release(Request $request, GelombangPengambilan $gelombang)
    {
        abort_unless(Gate::allows('operateWarehouse'), 403);
        abort_unless($request->user()->canAccessGudang((int) $gelombang->gudang_id), 403);

        $petugas = $request->filled('ditugaskan_ke') ? User::find($request->input('ditugaskan_ke')) : null;

        try {
            $this->gelombang->rilis($gelombang, $petugas);
        } catch (RuntimeException $e) {
            return back()->withErrors(['gelombang' => $e->getMessage()]);
        }

        return back()->with('success', "Gelombang {$gelombang->nomor} dirilis ke lantai gudang.");
    }

    public function complete(Request $request, GelombangPengambilan $gelombang)
    {
        abort_unless(Gate::allows('operateWarehouse'), 403);
        abort_unless($request->user()->canAccessGudang((int) $gelombang->gudang_id), 403);

        try {
            $this->gelombang->selesaikan($gelombang);
        } catch (RuntimeException $e) {
            return back()->withErrors(['gelombang' => $e->getMessage()]);
        }

        return back()->with('success', "Gelombang {$gelombang->nomor} selesai.");
    }

    public function destroy(Request $request, GelombangPengambilan $gelombang)
    {
        abort_unless(Gate::allows('operateWarehouse'), 403);
        abort_unless($request->user()->canAccessGudang((int) $gelombang->gudang_id), 403);

        try {
            $this->gelombang->batalkan($gelombang);
        } catch (RuntimeException $e) {
            return back()->withErrors(['gelombang' => $e->getMessage()]);
        }

        return back()->with('success', "Gelombang {$gelombang->nomor} dibatalkan, perintah pengambilannya dilepas kembali.");
    }
}
