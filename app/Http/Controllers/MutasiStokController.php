<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\MutasiStok;
use Illuminate\Http\Request;

class MutasiStokController extends Controller
{
    public function index(Request $r)
    {
        $this->authorize('viewAny', MutasiStok::class);
        $q = MutasiStok::with(['gudang', 'bahan', 'user'])->when($r->gudang_id, fn($x, $v) => $x->where('gudang_id', $v))->when($r->bahan_id, fn($x, $v) => $x->where('bahan_id', $v))->latest('tanggal');
        return view('mutasi_stoks.index', ['rows' => $q->paginate(100)->withQueryString(), 'gudangs' => Gudang::orderBy('nama')->get()]);
    }
}
