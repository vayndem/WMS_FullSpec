@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Kategori &amp; Mapping Jasa</h3>
            <p class="text-base-content/60">Kategori 98 dan 99 bersifat tetap; Accounting mengatur COA saat jasa selesai.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ($categories as $c)
                <form method="post" action="{{ route('kategori-jasa.update', $c) }}" class="card border border-base-300 bg-base-100 shadow-sm">
                    @csrf
                    @method('PUT')
                    <div class="p-4">
                        <div class="mb-3 flex items-center gap-2">
                            <span class="badge badge-primary badge-lg">{{ $c->display_code }}</span>
                            <h5 class="font-bold">{{ $c->name }}</h5>
                        </div>
                        <label class="label"><span class="label-text">{{ $c->requires_datapesanan ? 'COA WIP/Biaya Produksi' : 'COA Beban Operasional' }}</span></label>
                        <select name="expense_coa_id" class="select select-bordered mb-3 w-full">
                            @foreach ($accounts->filter(fn($a) => $c->requires_datapesanan ? ($a->kategori_akun === 'ASET' && $a->posisi_normal === 'DEBIT') : ($a->kategori_akun === 'BEBAN' && $a->posisi_normal === 'DEBIT')) as $a)
                                <option value="{{ $a->id }}" @selected($c->expense_coa_id === $a->id)>{{ $a->kode_akun }} — {{ $a->nama_akun }}</option>
                            @endforeach
                        </select>
                        <label class="label"><span class="label-text">COA GRNI Jasa</span></label>
                        <select name="grni_coa_id" class="select select-bordered mb-2 w-full">
                            @foreach ($accounts->filter(fn($a) => $a->kategori_akun === 'LIABILITAS' && $a->posisi_normal === 'KREDIT') as $a)
                                <option value="{{ $a->id }}" @selected($c->grni_coa_id === $a->id)>{{ $a->kode_akun }} — {{ $a->nama_akun }}</option>
                            @endforeach
                        </select>
                        <p class="text-sm text-base-content/50">
                            COA ini didebit ketika penerimaan jasa masuk invoice. Penerimaan jasa yang baru dibuat belum membentuk jurnal. Mapping GRNI disimpan sebagai kontrol konfigurasi dan persiapan accrual jasa.
                        </p>
                        <input type="hidden" name="is_active" value="1">
                    </div>
                    @can('update', $c)
                        <div class="flex justify-end border-t border-base-300 p-4">
                            <button class="btn btn-primary">Simpan Mapping</button>
                        </div>
                    @endcan
                </form>
            @endforeach
        </div>
    </div>
@endsection
