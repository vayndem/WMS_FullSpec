<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Http\Requests\StoreChartOfAccountRequest;
use App\Http\Requests\UpdateChartOfAccountRequest;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AccountingSetting;
use App\Models\KategoriBahan;
use App\Http\Requests\UpdateAccountingMappingRequest;
use Illuminate\Support\Facades\DB;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ChartOfAccount::class);

        if ($request->ajax()) {
            $query = ChartOfAccount::query();

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

        $accounts = ChartOfAccount::where('is_active', true)->where('is_postable', true)->orderBy('kode_akun')->get();
        $settings = AccountingSetting::pluck('coa_id', 'key');
        $categories = KategoriBahan::with(['coaPersediaan', 'coaBeban', 'coaClearingLpb'])->orderBy('katnama')->get();

        return view('coa.index', compact('accounts', 'settings', 'categories'));
    }

    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', ChartOfAccount::class);

        $filters = collect($request->input('filters', []))->filter(fn($value) => $value !== '');
        $search = trim((string) $request->input('search', ''));
        $fields = ['kode_akun', 'nama_akun', 'kategori_akun', 'posisi_normal', 'keterangan'];
        $query = ChartOfAccount::query()->orderBy('kode_akun');

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

        $rows = $query->limit(5000)->get()->map(fn($row) => [
            'kode_akun' => $row->kode_akun,
            'nama_akun' => $row->nama_akun,
            'kategori_akun' => $row->kategori_akun,
            'posisi_normal' => $row->posisi_normal,
            'keterangan' => $row->keterangan ?: '-',
        ]);

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Daftar Chart of Accounts',
            'columns' => [
                ['key' => 'kode_akun', 'label' => 'Kode Akun', 'align' => 'left'],
                ['key' => 'nama_akun', 'label' => 'Nama Akun', 'align' => 'left'],
                ['key' => 'kategori_akun', 'label' => 'Kategori', 'align' => 'left'],
                ['key' => 'posisi_normal', 'label' => 'Posisi Normal', 'align' => 'left'],
                ['key' => 'keterangan', 'label' => 'Keterangan', 'align' => 'left'],
            ],
            'rows' => $rows,
            'search' => $search,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('daftar-coa.pdf');
    }

    public function create()
    {
        $this->authorize('create', ChartOfAccount::class);

        return view('coa.create');
    }

    public function store(StoreChartOfAccountRequest $request)
    {
        $validated = $request->validated();

        $coa = ChartOfAccount::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Akun COA berhasil ditambahkan.',
            'data'    => $coa
        ], 201);
    }

    public function show($id)
    {
        $coa = ChartOfAccount::findOrFail($id);
        $this->authorize('view', $coa);

        return response()->json([
            'success' => true,
            'data'    => $coa
        ]);
    }

    public function edit($id)
    {
        $coa = ChartOfAccount::findOrFail($id);
        $this->authorize('update', $coa);

        return view('coa.edit', compact('coa'));
    }

    public function update(UpdateChartOfAccountRequest $request, $id)
    {
        $coa = ChartOfAccount::findOrFail($id);
        $this->authorize('update', $coa);

        $validated = $request->validated();
        if ($coa->isMapped() && (empty($validated['is_active']) || empty($validated['is_postable']))) {
            abort(422, 'Akun yang masih dipakai mapping tidak boleh dinonaktifkan. Pindahkan mapping terlebih dahulu.');
        }
        if (
            ($coa->isMapped() || $coa->jurnalDetails()->exists())
            && (
                $validated['kategori_akun'] !== $coa->kategori_akun
                || $validated['posisi_normal'] !== $coa->posisi_normal
            )
        ) {
            abort(422, 'Kategori akun dan posisi normal tidak boleh diubah setelah akun dipakai mapping atau jurnal.');
        }
        $coa->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Akun COA berhasil diperbarui.',
            'data'    => $coa
        ]);
    }

    public function destroy($id)
    {
        $coa = ChartOfAccount::findOrFail($id);
        $this->authorize('delete', $coa);

        $coa->update(['is_active' => false, 'is_cash_bank' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Akun COA dinonaktifkan agar histori jurnal tetap utuh.'
        ]);
    }

    public function getKasBank()
    {
        $coas = ChartOfAccount::where('is_active', true)
            ->where('is_postable', true)
            ->where('is_cash_bank', true)
            ->orderBy('kode_akun', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $coas,
            'postable' => ChartOfAccount::where('is_active', true)->where('is_postable', true)
                ->orderBy('kode_akun')->get(['id', 'kode_akun', 'nama_akun', 'kategori_akun']),
        ]);
    }

    public function updateMapping(UpdateAccountingMappingRequest $request)
    {
        DB::transaction(function () use ($request) {
            foreach ($request->validated('global') as $key => $coaId) {
                AccountingSetting::updateOrCreate(['key' => $key], ['coa_id' => $coaId]);
            }
            foreach ($request->validated('categories') as $categoryId => $mapping) {
                KategoriBahan::whereKey($categoryId)->update($mapping);
            }
        });

        return response()->json(['success' => true, 'message' => 'Mapping akuntansi berhasil disimpan.']);
    }
}
