@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="mb-3">
                <h4>Kategori & Mapping Jasa</h4>
                <p class="text-muted">Kategori 98 dan 99 bersifat tetap; Accounting mengatur COA saat jasa selesai.</p>
            </div>
            <div class="row g-3">
                @foreach ($categories as $c)
                    <div class="col-lg-6">
                        <form method="post" action="{{ route('service-categories.update', $c) }}"
                            class="card border-0 shadow-sm h-100">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <div class="d-flex gap-2 align-items-center mb-3">
                                    <span class="badge bg-primary fs-6">{{ $c->display_code }}</span>
                                    <h5 class="mb-0">{{ $c->name }}</h5>
                                </div>
                                <label class="form-label">
                                    {{ $c->requires_datapesanan ? 'COA WIP/Biaya Produksi' : 'COA Beban Operasional' }}
                                </label>
                                <select name="expense_coa_id" class="form-select mb-2">
                                    @foreach ($accounts->filter(fn($a) => $c->requires_datapesanan ? ($a->kategori_akun === 'ASET' && $a->posisi_normal === 'DEBIT') : ($a->kategori_akun === 'BEBAN' && $a->posisi_normal === 'DEBIT')) as $a)
                                        <option value="{{ $a->id }}" @selected($c->expense_coa_id === $a->id)>
                                            {{ $a->kode_akun }} — {{ $a->nama_akun }}
                                        </option>
                                    @endforeach
                                </select>
                                <label class="form-label mt-3">COA GRNI Jasa</label>
                                <select name="grni_coa_id" class="form-select mb-2">
                                    @foreach ($accounts->filter(fn($a) => $a->kategori_akun === 'LIABILITAS' && $a->posisi_normal === 'KREDIT') as $a)
                                        <option value="{{ $a->id }}" @selected($c->grni_coa_id === $a->id)>
                                            {{ $a->kode_akun }} — {{ $a->nama_akun }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    COA ini didebit ketika BAP masuk invoice. BAP yang baru dibuat belum membentuk
                                    jurnal. Mapping GRNI disimpan sebagai kontrol konfigurasi dan persiapan accrual jasa.
                                </div>
                                <input type="hidden" name="is_active" value="1">
                            </div>
                            @can('update', $c)
                                <div class="card-footer bg-white text-end">
                                    <button class="btn btn-primary">Simpan Mapping</button>
                                </div>
                            @endcan
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @include('layouts.template.page-help', [
        'title' => 'Mapping Jasa',
        'items' => [
            'Kategori 98 wajib cost center/departemen.',
            'Kategori 99 wajib alokasi Datapesanan 100%.',
            'BAP menandai pekerjaan mulai dan tidak membentuk jurnal.',
            'Mapping COA digunakan ketika BAP masuk invoice dan pekerjaan menjadi selesai.',
        ],
    ])
@endsection
