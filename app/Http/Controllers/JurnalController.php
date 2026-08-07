<?php

namespace App\Http\Controllers;

use App\Models\Jurnal;
use App\Models\ChartOfAccount;
use App\Http\Requests\StoreJurnalRequest;
use App\Http\Requests\UpdateJurnalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use App\Services\AccountingPeriodService;
use App\Services\DocumentNumberService;

class JurnalController extends Controller
{
    public function __construct(private AccountingPeriodService $periods, private DocumentNumberService $numbers) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Jurnal::class);

        if ($request->ajax()) {
            $query = Jurnal::with(['details.coa'])
                ->orderByDesc('tanggal')
                ->orderByDesc('id');

            $query
                ->when($request->filled('sumber_transaksi'), fn($builder) => $builder->where('sumber_transaksi', $request->input('sumber_transaksi')))
                ->when($request->filled('status'), fn($builder) => $builder->where('status', $request->input('status')))
                ->when($request->filled('date_from'), fn($builder) => $builder->whereDate('tanggal', '>=', $request->input('date_from')))
                ->when($request->filled('date_to'), fn($builder) => $builder->whereDate('tanggal', '<=', $request->input('date_to')));

            return datatables()->of($query)
                ->addIndexColumn()
                ->addColumn('can_update', function ($row) use ($request) {
                    return $request->user()->can('update', $row);
                })
                ->addColumn('can_delete', function ($row) use ($request) {
                    return $request->user()->can('delete', $row);
                })
                ->addColumn('can_post', fn($row) => $request->user()->can('post', $row))
                ->addColumn('can_reverse', fn($row) => $request->user()->can('reverse', $row))
                ->make(true);
        }

        return view('jurnal.index');
    }

    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', Jurnal::class);

        $filters = collect($request->input('filters', []))->filter(fn($value) => $value !== '');
        $search = trim((string) $request->input('search', ''));
        $fields = ['no_jurnal', 'tanggal', 'sumber_transaksi', 'keterangan', 'total_debit', 'total_kredit'];
        $query = Jurnal::query()
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        $query
            ->when($request->filled('sumber_transaksi'), fn($builder) => $builder->where('sumber_transaksi', $request->input('sumber_transaksi')))
            ->when($request->filled('status'), fn($builder) => $builder->where('status', $request->input('status')))
            ->when($request->filled('date_from'), fn($builder) => $builder->whereDate('tanggal', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn($builder) => $builder->whereDate('tanggal', '<=', $request->input('date_to')));

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
            'no_jurnal' => $row->no_jurnal,
            'tanggal' => $row->tanggal,
            'sumber_transaksi' => $row->sumber_transaksi,
            'keterangan' => $row->keterangan ?: '-',
            'total_debit' => 'Rp ' . number_format($row->total_debit, 0, ',', '.'),
            'total_kredit' => 'Rp ' . number_format($row->total_kredit, 0, ',', '.'),
        ]);

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Daftar Jurnal',
            'columns' => [
                ['key' => 'no_jurnal', 'label' => 'No Jurnal', 'align' => 'left'],
                ['key' => 'tanggal', 'label' => 'Tanggal', 'align' => 'left'],
                ['key' => 'sumber_transaksi', 'label' => 'Sumber', 'align' => 'left'],
                ['key' => 'keterangan', 'label' => 'Keterangan', 'align' => 'left'],
                ['key' => 'total_debit', 'label' => 'Total Debit', 'align' => 'right'],
                ['key' => 'total_kredit', 'label' => 'Total Kredit', 'align' => 'right'],
            ],
            'rows' => $rows,
            'search' => $search,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('daftar-jurnal.pdf');
    }

    public function create()
    {
        $this->authorize('create', Jurnal::class);

        $coas = ChartOfAccount::where('is_active', true)->where('is_postable', true)->orderBy('kode_akun')->get();
        $documentNumber = $this->numbers->financial('JR');

        return view('jurnal.create', compact('coas', 'documentNumber'));
    }

    public function store(StoreJurnalRequest $request)
    {
        $validated = $request->validated();
        $this->periods->assertOpen($validated['tanggal'], 'Jurnal manual');

        $jurnal = DB::transaction(function () use ($validated) {
            $jurnal = Jurnal::create([
                'no_jurnal'        => $validated['no_jurnal'],
                'tanggal'          => $validated['tanggal'],
                'keterangan'       => $validated['keterangan'] ?? null,
                'sumber_transaksi' => 'MANUAL',
                'reff_id'          => null,
                'status'           => 'DRAFT',
                'created_by'       => Auth::id(),
                'total_debit'      => collect($validated['details'])->sum('debit'),
                'total_kredit'     => collect($validated['details'])->sum('kredit'),
            ]);
            $jurnal->details()->createMany($validated['details']);
            return $jurnal;
        });

        return response()->json([
            'success' => true,
            'message' => 'Header Jurnal berhasil dibuat.',
            'data'    => $jurnal
        ], 201);
    }

    public function show($id)
    {
        $jurnal = Jurnal::with(['details.coa'])->findOrFail($id);
        $this->authorize('view', $jurnal);

        return response()->json([
            'success' => true,
            'data'    => $jurnal
        ]);
    }

    public function edit($id)
    {
        $jurnal = Jurnal::with(['details.coa'])->findOrFail($id);
        $this->authorize('update', $jurnal);

        $coas = ChartOfAccount::where('is_active', true)->where('is_postable', true)->orderBy('kode_akun')->get();

        return view('jurnal.edit', compact('jurnal', 'coas'));
    }

    public function update(UpdateJurnalRequest $request, $id)
    {
        $jurnal = Jurnal::findOrFail($id);
        $this->authorize('update', $jurnal);

        $validated = $request->validated();
        $this->periods->assertOpen($validated['tanggal'], 'Jurnal manual');

        DB::transaction(function () use ($validated, $jurnal) {
            $jurnal->update([
                'no_jurnal'        => $jurnal->no_jurnal,
                'tanggal'          => $validated['tanggal'],
                'keterangan'       => $validated['keterangan'] ?? null,
                'sumber_transaksi' => 'MANUAL',
                'reff_id'          => null,
                'total_debit'      => collect($validated['details'])->sum('debit'),
                'total_kredit'     => collect($validated['details'])->sum('kredit'),
            ]);
            $jurnal->details()->delete();
            $jurnal->details()->createMany($validated['details']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Header Jurnal berhasil diperbarui.',
            'data'    => $jurnal
        ]);
    }

    public function destroy($id)
    {
        $jurnal = Jurnal::findOrFail($id);
        $this->authorize('delete', $jurnal);

        DB::transaction(function () use ($jurnal) {
            $jurnal->details()->delete();
            $jurnal->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Jurnal beserta detailnya berhasil dihapus.'
        ]);
    }

    public function post($id)
    {
        $jurnal = Jurnal::with('details')->findOrFail($id);
        $this->authorize('post', $jurnal);
        $this->periods->assertOpen($jurnal->tanggal, 'Jurnal manual');
        $debit = round((float) $jurnal->details->sum('debit'), 2);
        $credit = round((float) $jurnal->details->sum('kredit'), 2);
        abort_if($debit <= 0 || abs($debit - $credit) > 0.01, 422, 'Jurnal belum seimbang.');
        $jurnal->update([
            'status' => 'POSTED',
            'posted_by' => Auth::id(),
            'posted_at' => now(),
            'total_debit' => $debit,
            'total_kredit' => $credit
        ]);

        return response()->json(['success' => true, 'message' => 'Jurnal berhasil diposting dan sekarang terkunci.']);
    }

    public function reverse($id)
    {
        $source = Jurnal::with('details')->findOrFail($id);
        $this->authorize('reverse', $source);
        $this->periods->assertOpen(now(), 'Reversal jurnal manual');

        $reversal = DB::transaction(function () use ($source) {
            $reversal = Jurnal::create([
                'no_jurnal' => $this->numbers->financial('JR', now()),
                'tanggal' => now()->toDateString(),
                'keterangan' => 'Pembalik jurnal ' . $source->no_jurnal,
                'sumber_transaksi' => 'REVERSAL',
                'reff_id' => $source->id,
                'status' => 'POSTED',
                'created_by' => Auth::id(),
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'reversal_of_id' => $source->id,
                'total_debit' => $source->total_kredit,
                'total_kredit' => $source->total_debit,
            ]);
            $reversal->details()->createMany($source->details->map(fn($line) => [
                'coa_id' => $line->coa_id,
                'debit' => $line->kredit,
                'kredit' => $line->debit,
                'keterangan' => 'Pembalik: ' . $line->keterangan,
            ])->all());
            $source->update(['status' => 'REVERSED']);
            return $reversal;
        });

        return response()->json(['success' => true, 'message' => "Jurnal dibalik oleh {$reversal->no_jurnal}."]);
    }
}
