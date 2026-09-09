@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex items-center gap-3">
            <a href="{{ route('bahan.show', $bahan) }}" class="btn btn-ghost border border-base-300" aria-label="Kembali">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h3 class="text-2xl font-bold">Perbarui Master Bahan</h3>
                <p class="text-base-content/60">Perbaiki identitas, gudang, dan konversi satuan. Kategori COA dikunci.</p>
            </div>
        </div>

        <form method="post" action="{{ route('bahan.update', $bahan) }}" class="card border border-base-300 bg-base-100 shadow-sm">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text font-semibold">Nama Bahan</span></label>
                    <input name="nama" class="input input-bordered" required maxlength="200" value="{{ old('nama', $bahan->nama) }}">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Kategori Bahan</span></label>
                    <input type="hidden" name="kategori" value="{{ $bahan->kategori }}">
                    <input class="input input-bordered bg-base-200" value="{{ $bahan->kategoriBahan->katnama ?? '-' }}" readonly>
                    <span class="label-text-alt mt-1 text-base-content/50">Dikunci karena kategori menentukan akun persediaan dan jurnal.</span>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Gudang Utama</span></label>
                    <select name="tipe_gudang" class="select select-bordered" required>
                        @foreach ($gudangs as $gudang)
                            <option value="{{ $gudang->id }}" @selected((int) old('tipe_gudang', $bahan->tipe_gudang) === $gudang->id)>
                                {{ $gudang->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Satuan Utama</span></label>
                    <input name="satuan" class="input input-bordered" required maxlength="50"
                        value="{{ old('satuan', $bahan->satuan) }}" placeholder="Contoh: barrel">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Batas Minimum/Planning</span></label>
                    <input type="number" step="any" min="0" name="planning" class="input input-bordered"
                        value="{{ old('planning', $bahan->planning) }}">
                </div>

                <div class="rounded-lg border border-base-300 p-4 md:col-span-3">
                    <h5 class="font-bold">Kontrol Lot &amp; Kedaluwarsa</h5>
                    <p class="text-sm text-base-content/50">Kalau diaktifkan, penerimaan barang ini tidak bisa disimpan tanpa nomor lot / tanggal kedaluwarsa. Ini yang membuat picking FEFO dan blokir stok kedaluwarsa benar-benar jalan.</p>
                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input type="hidden" name="wajib_lot" value="0">
                            <input type="checkbox" name="wajib_lot" value="1" class="checkbox checkbox-primary"
                                @checked(old('wajib_lot', $bahan->wajib_lot))>
                            <span class="label-text">Wajib nomor lot saat penerimaan</span>
                        </label>
                        <label class="label cursor-pointer justify-start gap-3">
                            <input type="hidden" name="wajib_expiry" value="0">
                            <input type="checkbox" name="wajib_expiry" value="1" class="checkbox checkbox-primary"
                                @checked(old('wajib_expiry', $bahan->wajib_expiry))>
                            <span class="label-text">Wajib tanggal kedaluwarsa saat penerimaan</span>
                        </label>
                    </div>
                </div>

                <div class="rounded-lg border border-base-300 p-4 md:col-span-3">
                    <h5 class="font-bold">Konversi Satuan Kecil</h5>
                    <p class="text-sm text-base-content/50">Contoh: 1 barrel berisi 10 kaleng. Isi jumlah kecil = 10 dan nama satuan = kaleng.</p>
                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="form-control">
                            <label class="label"><span class="label-text">Jumlah Unit Kecil per 1 Satuan Utama</span></label>
                            <input type="number" step="any" min="1.000001" name="berat_kecil" class="input input-bordered"
                                value="{{ old('berat_kecil', $bahan->satuan_kecil ? $bahan->berat_kecil : '') }}" placeholder="10">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text">Nama Satuan Kecil</span></label>
                            <input name="satuan_kecil" class="input input-bordered" maxlength="11"
                                value="{{ old('satuan_kecil', $bahan->satuan_kecil) }}" placeholder="kaleng">
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-base-content/50">Kosongkan keduanya bila bahan tidak memiliki satuan kecil.</p>
                </div>

                <div class="form-control md:col-span-3">
                    <label class="label"><span class="label-text font-semibold">Keterangan/Spesifikasi</span></label>
                    <textarea name="keterangan_bahan" class="textarea textarea-bordered" maxlength="200" rows="3">{{ old('keterangan_bahan', $bahan->keterangan_bahan) }}</textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 p-4">
                <a href="{{ route('bahan.show', $bahan) }}" class="btn btn-ghost border border-base-300">Batal</a>
                <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>

@endsection

@if ($errors->any())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Data belum dapat disimpan',
                    html: @json('<div class="text-start"><ul class="mb-0"><li>' . $errors->all()->map(fn($error) => e($error))->implode('</li><li>') . '</li></ul></div>'),
                    confirmButtonText: 'Periksa kembali',
                });
            });
        </script>
    @endpush
@endif
