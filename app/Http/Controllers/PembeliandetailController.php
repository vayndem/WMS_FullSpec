<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\RequestDetail;
use App\Http\Requests\StorePembelianDetailRequest;
use App\Http\Requests\UpdatePembelianDetailRequest;
use App\Traits\CalculatesPembelianTotals;
use Illuminate\Support\Facades\DB;

class PembelianDetailController extends Controller
{
    use CalculatesPembelianTotals;

    public function index($no_po)
    {
        $pembelian = Pembelian::where('no_po', $no_po)->firstOrFail();
        $this->authorize('view', $pembelian);

        $details = PembelianDetail::where('no_po', $no_po)->with('bahan')->get();

        return response()->json([
            'success' => true,
            'data'    => $details
        ]);
    }

    public function store(StorePembelianDetailRequest $request, $no_po)
    {
        $pembelian = Pembelian::where('no_po', $no_po)->firstOrFail();

        $this->authorize('create', [PembelianDetail::class, $pembelian]);

        $validated = $request->validated();

        DB::transaction(function () use ($pembelian, $validated) {
            $subExclude = $validated['jumlah'] * $validated['harga'];
            $subPpn = round(($subExclude * $pembelian->ppn) / 100, 2);

            $pembelian->details()->create([
                'bahan_id'          => $validated['bahan_id'],
                'jumlah'            => $validated['jumlah'],
                'harga'             => $validated['harga'],
                'exclude'           => $subExclude,
                'ppn'               => $subPpn,
                'include'           => $subExclude + $subPpn,
                'request_detail_id' => $validated['request_detail_id'] ?? null,
                'jenis'             => 0,
            ]);

            if (!empty($validated['request_detail_id'])) {
                $reqDetail = RequestDetail::find($validated['request_detail_id']);
                if ($reqDetail) {
                    $reqDetail->update([
                        'realisasi' => $reqDetail->pembelianDetails()->sum('jumlah')
                    ]);
                }
            }

            $this->recalculatePembelianTotals($pembelian);
        });

        return response()->json([
            'success' => true,
            'message' => 'Item detail berhasil ditambahkan ke PO.'
        ], 201);
    }

    public function update(UpdatePembelianDetailRequest $request, PembelianDetail $pembeliandetail)
    {
        $this->authorize('update', $pembeliandetail);

        $validated = $request->validated();
        $pembelian = $pembeliandetail->pembelian;

        DB::transaction(function () use ($pembeliandetail, $pembelian, $validated) {
            $subExclude = $validated['jumlah'] * $validated['harga'];
            $subPpn = round(($subExclude * $pembelian->ppn) / 100, 2);

            $pembeliandetail->update([
                'jumlah'  => $validated['jumlah'],
                'harga'   => $validated['harga'],
                'exclude' => $subExclude,
                'ppn'     => $subPpn,
                'include' => $subExclude + $subPpn,
            ]);

            if ($pembeliandetail->request_detail_id) {
                $reqDetail = RequestDetail::find($pembeliandetail->request_detail_id);
                if ($reqDetail) {
                    $reqDetail->update([
                        'realisasi' => $reqDetail->pembelianDetails()->sum('jumlah')
                    ]);
                }
            }

            $this->recalculatePembelianTotals($pembelian);
        });

        return response()->json([
            'success' => true,
            'message' => 'Baris item detail berhasil diperbarui.'
        ]);
    }

    public function destroy(PembelianDetail $pembeliandetail)
    {
        $this->authorize('delete', $pembeliandetail);

        $pembelian = $pembeliandetail->pembelian;

        DB::transaction(function () use ($pembeliandetail, $pembelian) {
            $reqDetailId = $pembeliandetail->request_detail_id;

            $pembeliandetail->delete();

            if ($reqDetailId) {
                $reqDetail = RequestDetail::find($reqDetailId);
                if ($reqDetail) {
                    $reqDetail->update([
                        'realisasi' => $reqDetail->pembelianDetails()->sum('jumlah')
                    ]);
                }
            }

            if ($pembelian->details()->count() === 0) {
                $pembelian->delete();
            } else {
                $this->recalculatePembelianTotals($pembelian);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Item detail berhasil dihapus.'
        ]);
    }
}
