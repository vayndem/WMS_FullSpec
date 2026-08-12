<?php

namespace App\Http\Controllers;

use App\Models\RequestDetail;
use App\Models\MaterialRequest;
use App\Http\Requests\StoreRequestDetailRequest;
use App\Http\Requests\UpdateRequestDetailRequest;
use Illuminate\Http\Request;
use App\Models\LpbDetail;
use App\Models\Lpb;

class RequestDetailController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', RequestDetail::class);

        if ($request->ajax()) {
            $mayViewPrice = $request->user()->hasAnyRole([\App\Models\User::ROLE_PURCHASING, \App\Models\User::ROLE_ACCOUNTING]);
            $query = RequestDetail::with('bahan', 'request')
                ->whereHas('request', function ($q) {
                    $q->where('status', MaterialRequest::APPROVED);
                })
                ->whereRaw('jumlah_acc > realisasi');

            $searchValue = $request->input('search.value');
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('nama_barang', 'like', "%{$searchValue}%")
                        ->orWhereHas('request', function ($header) use ($searchValue) {
                            $header->where('no_request', 'like', "%{$searchValue}%");
                        })
                        ->orWhereHas('bahan', function ($b) use ($searchValue) {
                            $b->where('nama', 'like', "%{$searchValue}%");
                        });
                });
            }

            return datatables()->of($query)
                ->addColumn('id_permintaan', function ($row) {
                    return $row->id;
                })
                ->addColumn('no_request', function ($row) {
                    return $row->request?->no_request ?? '-';
                })
                ->addColumn('bahan', function ($row) {
                    return $row->nama_barang;
                })
                ->addColumn('id_bahan', function ($row) {
                    return $row->bahan_id;
                })
                ->addColumn('jumlah_order', function ($row) {
                    return $row->jumlah_acc;
                })
                ->addColumn('harga', function ($row) use ($mayViewPrice) {
                    return $mayViewPrice ? $this->lastFiveLpbAverage((int) $row->bahan_id) : null;
                })
                ->addColumn('harga_referensi', function ($row) use ($mayViewPrice) {
                    return $mayViewPrice ? $this->lastFiveLpbAverage((int) $row->bahan_id) : null;
                })
                ->make(true);
        }

        return response()->json(['message' => 'Invalid endpoint'], 400);
    }

    private function lastFiveLpbAverage(int $bahanId): float
    {
        $receipts = LpbDetail::query()
            ->join('lpbs', 'lpbs.id_lpb', '=', 'lpb_details.id_lpb')
            ->where('lpb_details.id_bahan', $bahanId)
            ->where('lpb_details.jumlah_barang_diterima', '>', 0)
            ->where('lpbs.status', Lpb::POSTED)
            ->orderByDesc('lpbs.tanggal')
            ->orderByDesc('lpb_details.id')
            ->limit(5)
            ->get(['lpb_details.jumlah_barang_diterima', 'lpb_details.harga']);
        $quantity = (float) $receipts->sum('jumlah_barang_diterima');
        return $quantity > 0
            ? round($receipts->sum(fn($row) => (float) $row->jumlah_barang_diterima * (float) $row->harga) / $quantity, 4)
            : 0;
    }

    public function store(StoreRequestDetailRequest $request)
    {
        $validated = $request->validated();
        $validated['realisasi'] = 0;

        $detail = RequestDetail::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Detail request berhasil ditambahkan.',
            'data'    => $detail,
        ], 201);
    }

    public function show(RequestDetail $requestdetail)
    {
        $this->authorize('view', $requestdetail);

        return response()->json([
            'success' => true,
            'data'    => $requestdetail->load('bahan', 'request'),
        ]);
    }

    public function update(UpdateRequestDetailRequest $request, RequestDetail $requestdetail)
    {
        $validated = $request->validated();

        $requestdetail->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Detail request berhasil diperbarui.',
            'data'    => $requestdetail,
        ]);
    }

    public function destroy(RequestDetail $requestdetail)
    {
        $this->authorize('delete', $requestdetail);

        $requestdetail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Detail request berhasil dihapus.',
        ]);
    }
}
