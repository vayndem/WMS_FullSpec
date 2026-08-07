@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="{{ route('bahan.show', $bahan) }}" class="btn btn-light border" aria-label="Kembali">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h3 class="mb-1">Perbarui Master Bahan</h3>
                    <p class="text-muted mb-0">Perbaiki identitas, gudang, dan konversi satuan. Kategori COA dikunci.</p>
                </div>
            </div>

            <form method="post" action="{{ route('bahan.update', $bahan) }}" class="card border-0 shadow-sm">
                @csrf
                @method('PUT')
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nama bahan</label>
                            <input name="nama" class="form-control" required maxlength="200"
                                value="{{ old('nama', $bahan->nama) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kategori bahan</label>
                            <input type="hidden" name="kategori" value="{{ $bahan->kategori }}">
                            <input class="form-control" value="{{ $bahan->kategoriBahan->katnama ?? '-' }}" readonly>
                            <div class="form-text">Dikunci karena kategori menentukan akun persediaan dan jurnal.</div>
                            <small class="text-muted">Kategori ini menentukan mapping COA bahan.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gudang utama</label>
                            <select name="tipe_gudang" class="form-select" required>
                                @foreach ($gudangs as $gudang)
                                    <option value="{{ $gudang->id }}" @selected((int) old('tipe_gudang', $bahan->tipe_gudang) === $gudang->id)>
                                        {{ $gudang->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satuan utama</label>
                            <input name="satuan" class="form-control" required maxlength="50"
                                value="{{ old('satuan', $bahan->satuan) }}" placeholder="Contoh: barrel">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Batas minimum/planning</label>
                            <input type="number" step="any" min="0" name="planning" class="form-control"
                                value="{{ old('planning', $bahan->planning) }}">
                        </div>
                    </div>

                    <div class="rounded border p-3 mt-4">
                        <h5 class="mb-1">Konversi satuan kecil</h5>
                        <p class="text-muted small">Contoh: 1 barrel berisi 10 kaleng. Isi jumlah kecil = 10 dan nama satuan = kaleng.</p>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label">Jumlah unit kecil per 1 satuan utama</label>
                                <input type="number" step="any" min="1.000001" name="berat_kecil" class="form-control"
                                    value="{{ old('berat_kecil', $bahan->satuan_kecil ? $bahan->berat_kecil : '') }}"
                                    placeholder="10">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Nama satuan kecil</label>
                                <input name="satuan_kecil" class="form-control" maxlength="11"
                                    value="{{ old('satuan_kecil', $bahan->satuan_kecil) }}" placeholder="kaleng">
                            </div>
                            <div class="col-md-2">
                                <div class="small text-muted">Kosongkan keduanya bila bahan tidak memiliki satuan kecil.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Keterangan/spesifikasi</label>
                        <textarea name="keterangan_bahan" class="form-control" maxlength="200" rows="3">{{ old('keterangan_bahan', $bahan->keterangan_bahan) }}</textarea>
                    </div>
                </div>
                <div class="card-footer bg-transparent text-end p-3">
                    <a href="{{ route('bahan.show', $bahan) }}" class="btn btn-light border">Batal</a>
                    <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    @include('layouts.template.page-help', [
        'title' => 'Konversi Satuan Bahan',
        'items' => [
            'Stok dan layer tetap disimpan dalam satuan utama.',
            'NPK otomatis menggunakan satuan kecil jika konversinya tersedia.',
            'Mengubah konversi tidak mengubah stok lama; hanya cara input dan tampilan ekuivalennya.',
            'Kategori bahan terhubung langsung dengan mapping COA pada master Kategori & Mapping.',
        ],
    ])
@endsection

@if ($errors->any())
    @push('scripts')
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Data belum dapat disimpan',
                html: @json('<div class="text-start"><ul class="mb-0"><li>' . $errors->all()->map(fn($error) => e($error))->implode('</li><li>') . '</li></ul></div>'),
                confirmButtonText: 'Periksa kembali'
            });
        </script>
    @endpush
@endif
