<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        if ($request->ajax()) {
            $query = Supplier::query();

            return datatables()->of($query)
                ->addColumn('can_update', function ($row) use ($request) {
                    return $request->user()->can('update', $row);
                })
                ->addColumn('can_delete', function ($row) use ($request) {
                    return $request->user()->can('delete', $row);
                })
                ->make(true);
        }

        return view('supplier.index');
    }

    public function dataTable(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        if ($request->ajax()) {
            $query = Supplier::query();

            return datatables()->of($query)
                ->addColumn('aksi', function ($row) {
                    return '<button type="button" class="btn btn-sm btn-success btn-pilih-supplier" 
                        data-id="' . $row->id . '" 
                        data-nama="' . htmlspecialchars($row->nama, ENT_QUOTES) . '">
                        <i class="fa-solid fa-check mr-1"></i> Pilih
                    </button>';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }

        return response()->json(['message' => 'Invalid endpoint'], 400);
    }

    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', Supplier::class);

        $filters = collect($request->input('filters', []))->filter(fn ($value) => $value !== '');
        $search = trim((string) $request->input('search', ''));
        $fields = ['nama', 'alamat', 'npwp', 'telp', 'up', 'pembayaran'];
        $query = Supplier::query()->orderBy('nama');

        if ($search !== '') {
            $query->where(function ($builder) use ($fields, $search) {
                foreach ($fields as $field) {
                    $builder->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        foreach ($filters as $field => $value) {
            if (in_array($field, $fields, true)) {
                $query->where($field, 'like', "%{$value}%");
            }
        }

        $columns = collect($fields)->map(fn ($field) => [
            'key' => $field,
            'label' => $field === 'telp' ? 'Telepon' : ucfirst($field),
            'align' => 'left',
        ])->all();
        $rows = $query->limit(5000)->get()->map(fn ($row) => collect($fields)
            ->mapWithKeys(fn ($field) => [$field => $row->{$field} ?: '-'])->all());

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Daftar Supplier',
            'columns' => $columns,
            'rows' => $rows,
            'search' => $search,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('daftar-supplier.pdf');
    }

    public function create()
    {
        $this->authorize('create', Supplier::class);

        return view('supplier.create');
    }

    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();

        Supplier::create($validated);

        return redirect()->route('supplier.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        return view('supplier.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $validated = $request->validated();

        $supplier->update($validated);

        return redirect()->route('supplier.index')->with('success', 'Data supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete', $supplier);

        $supplier->delete();

        return redirect()->route('supplier.index')->with('success', 'Supplier berhasil dihapus.');
    }
}
