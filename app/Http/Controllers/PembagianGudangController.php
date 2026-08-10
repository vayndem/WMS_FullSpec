<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePembagianGudangRequest;
use App\Http\Requests\UpdatePembagianGudangRequest;
use App\Models\Gudang;
use App\Models\PembagianGudang;
use App\Models\User;

class PembagianGudangController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', PembagianGudang::class);
        return view('pembagian_gudangs.index', [
            'rows' => PembagianGudang::with(['user', 'gudang'])->paginate(50),
            'users' => User::whereIn('type', [User::ROLE_WAREHOUSE, User::ROLE_PRODUCTION])->orderBy('name')->get(),
            'gudangs' => Gudang::orderBy('nama')->get()
        ]);
    }
    public function store(StorePembagianGudangRequest $r)
    {
        $data = $r->validated();
        foreach (['boleh_menerima', 'boleh_npk', 'boleh_transfer', 'boleh_opname'] as $f) $data[$f] = $r->boolean($f);
        PembagianGudang::updateOrCreate(['user_id' => $data['user_id'], 'gudang_id' => $data['gudang_id']], $data);
        return back()->with('success', 'Pembagian gudang disimpan.');
    }
    public function update(UpdatePembagianGudangRequest $r, PembagianGudang $pembagianGudang)
    {
        $data = $r->validated();
        foreach (['boleh_menerima', 'boleh_npk', 'boleh_transfer', 'boleh_opname'] as $f) $data[$f] = $r->boolean($f);
        $pembagianGudang->update($data);
        return back()->with('success', 'Pembagian gudang diperbarui.');
    }
    public function destroy(PembagianGudang $pembagianGudang)
    {
        $this->authorize('delete', $pembagianGudang);
        $pembagianGudang->delete();
        return back()->with('success', 'Pembagian gudang dihapus.');
    }
}
