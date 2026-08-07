<?php

namespace App\Http\Controllers;

use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Http\Requests\StoreJurnalDetailRequest;
use App\Http\Requests\UpdateJurnalDetailRequest;
use Illuminate\Support\Facades\DB;

class JurnalDetailController extends Controller
{
    public function store(StoreJurnalDetailRequest $request)
    {
        $validated = $request->validated();

        $detail = DB::transaction(function () use ($validated) {
            $jurnal = Jurnal::findOrFail($validated['jurnal_id']);

            $item = JurnalDetail::create([
                'jurnal_id'  => $jurnal->id,
                'coa_id'     => $validated['coa_id'],
                'debit'      => $validated['debit'],
                'kredit'     => $validated['kredit'],
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            $totalDebit = $jurnal->details()->sum('debit');
            $totalKredit = $jurnal->details()->sum('kredit');

            $jurnal->update([
                'total_debit'  => $totalDebit,
                'total_kredit' => $totalKredit,
            ]);

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Baris detail jurnal berhasil ditambahkan.',
            'data'    => $detail
        ], 201);
    }

    public function update(UpdateJurnalDetailRequest $request, $id)
    {
        $detail = JurnalDetail::with('jurnal')->findOrFail($id);
        $jurnal = $detail->jurnal;

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $detail, $jurnal) {
            $detail->update([
                'coa_id'     => $validated['coa_id'],
                'debit'      => $validated['debit'],
                'kredit'     => $validated['kredit'],
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            $totalDebit = $jurnal->details()->sum('debit');
            $totalKredit = $jurnal->details()->sum('kredit');

            $jurnal->update([
                'total_debit'  => $totalDebit,
                'total_kredit' => $totalKredit,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Baris detail jurnal berhasil diperbarui.',
            'data'    => $detail
        ]);
    }

    public function destroy($id)
    {
        $detail = JurnalDetail::with('jurnal')->findOrFail($id);
        $jurnal = $detail->jurnal;

        $this->authorize('update', $jurnal);

        DB::transaction(function () use ($detail, $jurnal) {
            $detail->delete();

            $totalDebit = $jurnal->details()->sum('debit');
            $totalKredit = $jurnal->details()->sum('kredit');

            $jurnal->update([
                'total_debit'  => $totalDebit,
                'total_kredit' => $totalKredit,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Baris detail jurnal berhasil dihapus.'
        ]);
    }
}
