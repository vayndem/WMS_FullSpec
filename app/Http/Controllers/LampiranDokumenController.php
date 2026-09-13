<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLampiranDokumenRequest;
use App\Models\LampiranDokumen;
use App\Services\LampiranService;
use RuntimeException;

class LampiranDokumenController extends Controller
{
    public function __construct(private LampiranService $lampiran) {}

    public function store(StoreLampiranDokumenRequest $request)
    {
        $validated = $request->validated();
        $induk = $this->lampiran->induk($validated['lampiran_type'], (int) $validated['lampiran_id']);

        $this->authorize('view', $induk);

        try {
            $this->lampiran->simpan(
                $induk,
                $request->file('berkas'),
                $validated['kategori'],
                $validated['keterangan'] ?? null,
                $request->user(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['berkas' => $e->getMessage()]);
        }

        return back()->with('success', 'Lampiran berhasil diunggah.');
    }

    public function download(LampiranDokumen $lampiran)
    {
        $this->authorize('view', $lampiran);

        try {
            return $this->lampiran->unduh($lampiran);
        } catch (RuntimeException $e) {
            return back()->withErrors(['lampiran' => $e->getMessage()]);
        }
    }

    public function destroy(LampiranDokumen $lampiran)
    {
        $this->authorize('delete', $lampiran);

        $this->lampiran->hapus($lampiran);

        return back()->with('success', 'Lampiran berhasil dihapus.');
    }
}
