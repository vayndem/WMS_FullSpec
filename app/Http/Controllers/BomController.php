<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBomRequest;
use App\Http\Requests\UpdateBomStatusRequest;
use App\Models\Bahan;
use App\Models\Bom;
use App\Services\BomService;
use Illuminate\Http\Request;
use RuntimeException;

class BomController extends Controller
{
    public function __construct(private BomService $bom) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Bom::class);

        $daftar = Bom::with(['bahan', 'details.bahan'])
            ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('status')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('bom.index', [
            'daftar' => $daftar,
            'status' => $request->input('status'),
            'bahan' => Bahan::orderBy('nama')->limit(500)->get(['id', 'nama', 'satuan']),
        ]);
    }

    public function store(StoreBomRequest $request)
    {
        try {
            $bom = $this->bom->buat($request->validated(), $request->user());
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['bom' => $e->getMessage()]);
        }

        return redirect()->route('bom.index')->with('success', "BOM {$bom->kode} dibuat.");
    }

    public function status(UpdateBomStatusRequest $request, Bom $bom)
    {
        try {
            $this->bom->ubahStatus($bom, $request->validated()['status']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['bom' => $e->getMessage()]);
        }

        return back()->with('success', "Status BOM {$bom->kode} diperbarui.");
    }

    public function destroy(Bom $bom)
    {
        $this->authorize('delete', $bom);

        try {
            $this->bom->hapus($bom);
        } catch (RuntimeException $e) {
            return back()->withErrors(['bom' => $e->getMessage()]);
        }

        return redirect()->route('bom.index')->with('success', 'BOM dihapus.');
    }
}
