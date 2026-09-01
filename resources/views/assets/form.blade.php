@extends('layouts.app')
@section('content')
    @php($editing = isset($asset))
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">{{ $editing ? 'Edit' : 'Tambah' }} Asset</h3>
            <p class="text-base-content/60">Penyimpanan membentuk jurnal perolehan secara otomatis.</p>
        </div>
        <form method="post" action="{{ $editing ? route('assets.update', $asset) : route('assets.store') }}" class="card border border-base-300 bg-base-100 shadow-sm">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Nomor Asset</span></label>
                    <input name="asset_number" class="input input-bordered bg-base-200" value="{{ $editing ? $asset->asset_number : $documentNumber }}" readonly>
                    <span class="label-text-alt mt-1 text-base-content/50">Kode finansial internal dengan penanda AS.</span>
                </div>
                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text font-semibold">Nama Asset *</span></label>
                    <input required name="name" class="input input-bordered" value="{{ old('name', $asset->name ?? '') }}">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Kategori Asset *</span></label>
                    <select required name="asset_category_id" class="select select-bordered" data-app-picker data-placeholder="Cari kode atau kategori asset...">
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('asset_category_id', $asset->asset_category_id ?? null) == $c->id)>{{ $c->code }} — {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Nomor Seri</span></label>
                    <input name="serial_number" class="input input-bordered" value="{{ old('serial_number', $asset->serial_number ?? '') }}">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Lokasi</span></label>
                    <input name="location" class="input input-bordered" value="{{ old('location', $asset->location ?? '') }}">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Penanggung Jawab</span></label>
                    <input name="responsible_person" class="input input-bordered" value="{{ old('responsible_person', $asset->responsible_person ?? '') }}">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Kondisi *</span></label>
                    <select name="condition" class="select select-bordered">
                        @foreach (['BAIK', 'PERLU_SERVIS', 'RUSAK'] as $v)
                            <option @selected(old('condition', $asset->condition ?? 'BAIK') === $v)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Tanggal Perolehan *</span></label>
                    <input required type="date" name="acquisition_date" class="input input-bordered"
                        value="{{ old('acquisition_date', isset($asset) ? $asset->acquisition_date->format('Y-m-d') : today()->format('Y-m-d')) }}">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Jenis Perolehan *</span></label>
                    <select name="acquisition_type" class="select select-bordered">
                        @foreach (['OPENING_BALANCE' => 'Saldo Awal', 'CASH' => 'Tunai', 'CREDIT' => 'Hutang', 'GRANT' => 'Hibah', 'CORRECTION' => 'Koreksi'] as $k => $v)
                            <option value="{{ $k }}" @selected(old('acquisition_type', $asset->acquisition_type ?? null) === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">COA Lawan Perolehan *</span></label>
                    <select required name="acquisition_credit_coa_id" class="select select-bordered" data-app-picker data-placeholder="Cari kode atau nama akun...">
                        <option value="">Pilih akun</option>
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}" @selected(old('acquisition_credit_coa_id', $asset->acquisition_credit_coa_id ?? null) == $a->id)>{{ $a->kode_akun }} — {{ $a->nama_akun }}</option>
                        @endforeach
                    </select>
                </div>
                @foreach ([['acquisition_cost', 'Harga Perolehan'], ['residual_value', 'Nilai Residu'], ['opening_accumulated_depreciation', 'Akumulasi Penyusutan Awal']] as [$n, $l])
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">{{ $l }} *</span></label>
                        <input required type="number" min="0" step=".01" name="{{ $n }}" class="input input-bordered" value="{{ old($n, $asset->$n ?? 0) }}">
                    </div>
                @endforeach
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Masa Manfaat (bulan)</span></label>
                    <input type="number" min="1" name="useful_life_months" class="input input-bordered" value="{{ old('useful_life_months', $asset->useful_life_months ?? '') }}">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Tanggal Mulai Penyusutan</span></label>
                    <input type="date" name="depreciation_start_date" class="input input-bordered"
                        value="{{ old('depreciation_start_date', isset($asset) && $asset->depreciation_start_date ? $asset->depreciation_start_date->format('Y-m-d') : '') }}">
                    <span class="label-text-alt mt-1 text-base-content/50">Disiapkan untuk standar mendatang; boleh kosong.</span>
                </div>
                <div class="form-control md:col-span-4">
                    <label class="label"><span class="label-text font-semibold">Catatan</span></label>
                    <textarea name="notes" class="textarea textarea-bordered">{{ old('notes', $asset->notes ?? '') }}</textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 p-4">
                <a href="{{ route('assets.index') }}" class="btn btn-ghost border border-base-300">Batal</a>
                <button class="btn btn-primary">Simpan & Posting</button>
            </div>
        </form>
    </div>
@endsection
