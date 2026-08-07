<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGudangRequest;
use App\Http\Requests\UpdateGudangRequest;
use App\Models\Gudang;

class GudangController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Gudang::class);
        return view('gudangs.index', ['gudangs' => Gudang::orderBy('nama')->get()]);
    }
    public function create()
    {
        $this->authorize('create', Gudang::class);
        return view('gudangs.form', ['gudang' => new Gudang]);
    }
    public function store(StoreGudangRequest $r)
    {
        $data = $r->validated();
        foreach (['aktif', 'boleh_penerimaan', 'boleh_npk', 'boleh_transfer', 'boleh_opname'] as $f) $data[$f] = $r->boolean($f);
        $g = Gudang::create($data);
        return redirect()->route('gudangs.show', $g)->with('success', 'Gudang berhasil dibuat.');
    }
    public function show(Gudang $gudang)
    {
        $this->authorize('view', $gudang);
        return view('gudangs.show', ['gudang' => $gudang->loadCount('stok')]);
    }
    public function edit(Gudang $gudang)
    {
        $this->authorize('update', $gudang);
        return view('gudangs.form', compact('gudang'));
    }
    public function update(UpdateGudangRequest $r, Gudang $gudang)
    {
        $data = $r->validated();
        foreach (['aktif', 'boleh_penerimaan', 'boleh_npk', 'boleh_transfer', 'boleh_opname'] as $f) $data[$f] = $r->boolean($f);
        $gudang->update($data);
        return redirect()->route('gudangs.show', $gudang)->with('success', 'Gudang berhasil diperbarui.');
    }
}
