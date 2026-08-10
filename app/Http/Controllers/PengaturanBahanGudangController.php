<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengaturanBahanGudangRequest;
use App\Http\Requests\UpdatePengaturanBahanGudangRequest;
use App\Models\Bahan;
use App\Models\Gudang;
use App\Models\PengaturanBahanGudang;

class PengaturanBahanGudangController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', PengaturanBahanGudang::class);
        return view('pengaturan_bahan_gudangs.index', ['rows' => PengaturanBahanGudang::with(['gudang', 'bahan'])->paginate(50), 'gudangs' => Gudang::where('aktif', true)->orderBy('nama')->get(), 'bahans' => Bahan::orderBy('nama')->get()]);
    }
    public function store(StorePengaturanBahanGudangRequest $r)
    {
        $data = $r->validated();
        $data['aktif'] = $r->boolean('aktif');
        PengaturanBahanGudang::updateOrCreate(['gudang_id' => $data['gudang_id'], 'bahan_id' => $data['bahan_id']], $data);
        return back()->with('success', 'Pengaturan bahan gudang disimpan.');
    }
    public function update(UpdatePengaturanBahanGudangRequest $r, PengaturanBahanGudang $pengaturanBahanGudang)
    {
        $data = $r->validated();
        $data['aktif'] = $r->boolean('aktif');
        $pengaturanBahanGudang->update($data);
        return back()->with('success', 'Pengaturan diperbarui.');
    }
    public function destroy(PengaturanBahanGudang $pengaturanBahanGudang)
    {
        $this->authorize('delete', $pengaturanBahanGudang);
        $pengaturanBahanGudang->delete();
        return back()->with('success', 'Pengaturan dihapus.');
    }
}
