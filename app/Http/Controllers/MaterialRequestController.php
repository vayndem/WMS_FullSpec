<?php

namespace App\Http\Controllers;

use App\Models\MaterialRequest;
use App\Models\RequestDetail;
use App\Models\Bahan;
use App\Models\Gudang;
use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\ApproveMaterialRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\DocumentNumberService;
use App\Models\KategoriBahan;
use Illuminate\Validation\ValidationException;

class MaterialRequestController extends Controller
{
    public function __construct(private DocumentNumberService $numbers) {}
    public function index(Request $request)
    {
        $this->authorize('viewAny', MaterialRequest::class);

        if ($request->ajax()) {
            $status = $request->input('status');

            $query = MaterialRequest::with(['details'])
                ->when(!empty($status), function ($q) use ($status) {
                    return $q->where('status', $status);
                });

            return datatables()->of($query)
                ->addColumn('formatted_date', function ($row) {
                    return $row->created_at ? $row->created_at->format('d-m-Y H:i') : '-';
                })
                ->addColumn('can_approve', function ($row) use ($request) {
                    return $row->status === MaterialRequest::PENDING && $request->user()->can('approve', $row);
                })
                ->make(true);
        }

        return view('request.index');
    }

    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', MaterialRequest::class);

        $filters = collect($request->input('filters', []))->filter(fn($value) => $value !== '');
        $search = trim((string) $request->input('search', ''));
        $query = MaterialRequest::query()
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest();

        if ($search !== '') {
            $query->where(fn($q) => $q->where('no_request', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('created_at', 'like', "%{$search}%"));
        }

        foreach (['no_request', 'status', 'created_at'] as $field) {
            if ($filters->has($field)) {
                $query->where($field, 'like', "%{$filters[$field]}%");
            }
        }

        $rows = $query->limit(5000)->get()->map(fn($row) => [
            'no_request' => $row->no_request,
            'status' => ucfirst($row->status),
            'created_at' => optional($row->created_at)->format('d-m-Y H:i'),
        ]);

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Daftar Request',
            'columns' => [
                ['key' => 'no_request', 'label' => 'No Request', 'align' => 'left'],
                ['key' => 'status', 'label' => 'Status', 'align' => 'left'],
                ['key' => 'created_at', 'label' => 'Tanggal Request', 'align' => 'left'],
            ],
            'rows' => $rows,
            'search' => $search,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->stream('daftar-request.pdf');
    }

    public function create()
    {
        $this->authorize('create', MaterialRequest::class);

        $bahans = Bahan::orderBy('nama', 'asc')->get();
        $gudangs = Gudang::where('aktif', true)->orderBy('nama')->get();
        $kategoris = KategoriBahan::orderBy('katnama')->get();
        $documentNumber = $this->numbers->internal('REQ', 'PO');

        return view('request.create', compact('bahans', 'gudangs', 'kategoris', 'documentNumber'));
    }

    public function store(StoreMaterialRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $reqHeader = MaterialRequest::create([
                'no_request' => $validated['no_request'],
                'status'     => MaterialRequest::PENDING,
            ]);

            foreach ($validated['items'] as $item) {
                RequestDetail::create([
                    'request_id'   => $reqHeader->id,
                    'bahan_id'     => $item['bahan_id'] ?? null,
                    'nama_barang'  => $item['nama_barang'],
                    'jumlah_minta' => $item['jumlah_minta'],
                    'keterangan'   => $item['keterangan'] ?? null,
                    'realisasi'    => 0,
                    'kategori'     => $item['kategori'] ?? null,
                    'satuan'       => $item['satuan'] ?? null,
                    'berat_kecil'  => $item['berat_kecil'] ?? 1.00,
                    'satuan_kecil' => $item['satuan_kecil'] ?? null,
                    'tipe_gudang'  => $item['tipe_gudang'] ?? null,
                    'tipe_barang'  => $item['tipe_barang'] ?? null,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Request berhasil dibuat.'
        ]);
    }

    public function approveForm(MaterialRequest $request)
    {
        $this->authorize('approve', $request);

        $requestData = $request->load(['details.bahan', 'details.kategoriBahan', 'details.gudang']);

        return view('request.approve', compact('requestData'));
    }

    public function processApprove(ApproveMaterialRequest $formRequest, MaterialRequest $request)
    {
        $validated = $formRequest->validated();
        $user = Auth::user();

        DB::transaction(function () use ($validated, $request, $user) {
            foreach ($validated['items'] as $detailId => $itemData) {
                $detail = RequestDetail::findOrFail($detailId);

                if (!$detail->bahan_id) {
                    $this->authorize('create', Bahan::class);
                    $category = KategoriBahan::findOrFail($detail->tipe_barang);
                    if (!$category->coa_persediaan_id || !$category->coa_beban_id || !$category->coa_clearing_lpb_id) {
                        throw ValidationException::withMessages([
                            'kategori' => "Kategori {$category->katnama} belum memiliki mapping COA persediaan, beban, dan GRNI yang lengkap.",
                        ]);
                    }
                    $newBahan = Bahan::create([
                        'nama'                 => $detail->nama_barang,
                        'keterangan_bahan'     => $detail->keterangan,
                        'satuan'               => $detail->satuan,
                        'kategori'             => $detail->tipe_barang,
                        'berat_kecil'          => $detail->satuan_kecil ? $detail->berat_kecil : 1.00,
                        'satuan_kecil'         => $detail->satuan_kecil,
                        'tipe_gudang'          => $detail->tipe_gudang,
                        'tipe_barang'          => $detail->tipe_barang,
                        'stok_onhand'          => 0,
                        'stok_onpurchase'      => 0,
                        'planning'             => 0,
                        'stokawal'             => 0,
                        'pengambilan_stokawal' => 0,
                    ]);

                    $detail->bahan_id = $newBahan->id;
                }

                $detail->update([
                    'jumlah_acc' => $itemData['jumlah_acc'],
                ]);
            }

            $request->update([
                'status'           => MaterialRequest::APPROVED,
                'catatan_approver' => $validated['catatan_approver'] ?? null,
                'approved_by'      => $user->id,
                'approved_at'      => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Request berhasil disetujui.'
        ]);
    }
}
