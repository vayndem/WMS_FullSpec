<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKitRequest;
use App\Http\Requests\StorePerakitanKitRequest;
use App\Models\Bahan;
use App\Models\Gudang;
use App\Models\Kit;
use App\Models\PerakitanKit;
use App\Services\KittingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class KitController extends Controller
{
    public function __construct(private KittingService $kitting) {}

    public function index(Request $request)
    {
        abort_unless(Gate::allows('operateWarehouse'), 403);

        $gudangIds = $request->user()->accessibleGudangIds();

        return view('kit.index', [
            'kits' => Kit::with('bahanHasil', 'komponen.bahan')->orderBy('nama')->get(),
            'gudangs' => Gudang::whereIn('id', $gudangIds)->where('aktif', true)->orderBy('nama')->get(),
            'bahans' => Bahan::orderBy('nama')->limit(500)->get(),
            'perakitan' => PerakitanKit::with('kit.bahanHasil', 'gudang')
                ->whereIn('gudang_id', $gudangIds)
                ->latest('id')->limit(25)->get(),
        ]);
    }

    public function store(StoreKitRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $kit = Kit::create([
                'kode' => $data['kode'],
                'nama' => $data['nama'],
                'bahan_hasil_id' => $data['bahan_hasil_id'],
                'jumlah_hasil' => $data['jumlah_hasil'],
                'aktif' => true,
                'catatan' => $data['catatan'] ?? null,
            ]);

            foreach ($data['komponen'] as $komponen) {
                $kit->komponen()->create([
                    'bahan_id' => $komponen['bahan_id'],
                    'jumlah' => $komponen['jumlah'],
                ]);
            }
        });

        return redirect()->route('kit.index')->with('success', 'Kit berhasil dibuat.');
    }

    public function ketersediaan(Request $request, Kit $kit)
    {
        abort_unless(Gate::allows('operateWarehouse'), 403);

        $gudangId = (int) $request->input('gudang_id');
        abort_unless($request->user()->canAccessGudang($gudangId), 403);

        try {
            return response()->json([
                'success' => true,
                'komponen' => $this->kitting->ketersediaan($kit, $gudangId, (float) $request->input('jumlah_kit', 1)),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function rakit(StorePerakitanKitRequest $request, Kit $kit)
    {
        $data = $request->validated();

        try {
            $perakitan = $data['jenis'] === PerakitanKit::URAI
                ? $this->kitting->urai($kit, (int) $data['gudang_id'], (float) $data['jumlah_kit'], $data['tanggal'], $data['catatan'] ?? null)
                : $this->kitting->rakit($kit, (int) $data['gudang_id'], (float) $data['jumlah_kit'], $data['tanggal'], $data['catatan'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['perakitan' => $e->getMessage()])->withInput();
        }

        return redirect()->route('kit.index')->with('success',
            "{$perakitan->nomor} diposting. Nilai " . number_format((float) $perakitan->nilai_total, 2, ',', '.') . '.');
    }

    public function destroy(Kit $kit)
    {
        abort_unless(Gate::allows('operateWarehouse'), 403);
        abort_if($kit->perakitan()->exists(), 422, 'Kit yang sudah pernah dirakit tidak dapat dihapus, nonaktifkan saja.');

        $kit->delete();

        return redirect()->route('kit.index')->with('success', 'Kit dihapus.');
    }
}
