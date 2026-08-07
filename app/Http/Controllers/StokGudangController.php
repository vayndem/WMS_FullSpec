<?php
namespace App\Http\Controllers;
use App\Models\Gudang; use App\Models\StokGudang; use Illuminate\Http\Request;
class StokGudangController extends Controller
{
    public function index(Request $r){ $this->authorize('viewAny',StokGudang::class); $q=StokGudang::with(['gudang','bahan.tipeBarang'])->when($r->gudang_id,fn($x,$id)=>$x->where('gudang_id',$id))->when($r->q,fn($x,$v)=>$x->whereHas('bahan',fn($b)=>$b->where('nama','like',"%$v%"))); return view('stok_gudangs.index',['rows'=>$q->orderBy('gudang_id')->paginate(50)->withQueryString(),'gudangs'=>Gudang::where('aktif',true)->orderBy('nama')->get()]); }
    public function show(StokGudang $stokGudang){ $this->authorize('view',$stokGudang); return view('stok_gudangs.show',['stok'=>$stokGudang->load(['gudang','bahan']),'mutasi'=>\App\Models\MutasiStok::where('gudang_id',$stokGudang->gudang_id)->where('bahan_id',$stokGudang->bahan_id)->latest('tanggal')->paginate(50)]); }
}
