<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateKategoriJasaRequest;
use App\Models\BaganAkun;
use App\Models\KategoriJasa;

class KategoriJasaController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', KategoriJasa::class);
        $categories = KategoriJasa::with(['expenseAccount', 'grniAccount'])->orderBy('display_code')->get();
        $accounts = BaganAkun::where('is_active', true)->where('is_postable', true)->orderBy('kode_akun')->get();
        return view('kategori_jasa.index', compact('categories', 'accounts'));
    }
    public function update(UpdateKategoriJasaRequest $request, KategoriJasa $serviceCategory)
    {
        $serviceCategory->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Mapping kategori jasa diperbarui.');
    }
}
