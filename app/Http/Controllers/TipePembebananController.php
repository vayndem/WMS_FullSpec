<?php

namespace App\Http\Controllers;

use App\Models\TipePembebanan;
use App\Http\Requests\StoreTipePembebananRequest;
use App\Http\Requests\UpdateTipePembebananRequest;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TipePembebananController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', TipePembebanan::class);

        if ($request->ajax()) {
            $query = TipePembebanan::query();

            return datatables()->of($query)
                ->addIndexColumn()
                ->addColumn('can_update', function ($row) use ($request) {
                    return $request->user()->can('update', $row);
                })
                ->addColumn('can_delete', function ($row) use ($request) {
                    return $request->user()->can('delete', $row);
                })
                ->make(true);
        }

        return view('tipe_pembebanan.index');
    }

    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', TipePembebanan::class);

        $filters = collect($request->input('filters', []))->filter(fn ($value) => $value !== '');
        $search = trim((string) $request->input('search', ''));
        $query = TipePembebanan::query()->orderBy('nama_tipe');

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('nama_tipe', 'like', "%{$search}%")
                ->orWhere('keterangan', 'like', "%{$search}%"));
        }

        foreach (['nama_tipe', 'keterangan'] as $field) {
            if ($filters->has($field)) {
                $query->where($field, 'like', "%{$filters[$field]}%");
            }
        }

        $rows = $query->limit(5000)->get()->map(fn ($row) => [
            'nama_tipe' => $row->nama_tipe,
            'keterangan' => $row->keterangan ?: '-',
        ]);

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Daftar Tipe Pembebanan',
            'columns' => [
                ['key' => 'nama_tipe', 'label' => 'Nama Tipe', 'align' => 'left'],
                ['key' => 'keterangan', 'label' => 'Keterangan', 'align' => 'left'],
            ],
            'rows' => $rows,
            'search' => $search,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->stream('daftar-tipe-pembebanan.pdf');
    }

    public function create()
    {
        $this->authorize('create', TipePembebanan::class);

        return view('tipe_pembebanan.create');
    }

    public function store(StoreTipePembebananRequest $request)
    {
        $validated = $request->validated();

        $tipe = TipePembebanan::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tipe pembebanan berhasil ditambahkan.',
            'data'    => $tipe
        ], 201);
    }

    public function show($id)
    {
        $tipe = TipePembebanan::findOrFail($id);
        $this->authorize('view', $tipe);

        return response()->json([
            'success' => true,
            'data'    => $tipe
        ]);
    }

    public function edit($id)
    {
        $tipe = TipePembebanan::findOrFail($id);
        $this->authorize('update', $tipe);

        return view('tipe_pembebanan.edit', compact('tipe'));
    }

    public function update(UpdateTipePembebananRequest $request, $id)
    {
        $tipe = TipePembebanan::findOrFail($id);
        $this->authorize('update', $tipe);

        $validated = $request->validated();
        $tipe->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tipe pembebanan berhasil diperbarui.',
            'data'    => $tipe
        ]);
    }

    public function destroy($id)
    {
        $tipe = TipePembebanan::findOrFail($id);
        $this->authorize('delete', $tipe);

        $tipe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tipe pembebanan berhasil dihapus.'
        ]);
    }
}
