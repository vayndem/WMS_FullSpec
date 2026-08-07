@extends('layouts.app')
@section('content')
    @php($editing = isset($asset))
    <div class="content-page">
        <div class="container-fluid">
            <div class="mb-3">
                <h4>{{ $editing ? 'Edit' : 'Tambah' }} Asset</h4>
                <p class="text-muted">Penyimpanan membentuk jurnal perolehan secara otomatis.</p>
            </div>
            <form method="post" action="{{ $editing ? route('assets.update', $asset) : route('assets.store') }}"
                class="card border-0 shadow-sm create-section">
                <div class="card-body row g-3">@csrf @if ($editing)
                        @method('PUT')
                    @endif
                    <div class="col-md-3"><label class="form-label">Nomor Asset</label><input name="asset_number"
                            class="form-control" value="{{ $editing ? $asset->asset_number : $documentNumber }}"
                            readonly><small class="text-muted">Kode finansial internal dengan penanda AS.</small></div>
                    <div class="col-md-5"><label class="form-label">Nama Asset *</label><input required name="name"
                            class="form-control" value="{{ old('name', $asset->name ?? '') }}"></div>
                    <div class="col-md-4"><label class="form-label">Kategori Asset *</label><select required
                            name="asset_category_id" class="form-select" data-app-picker
                            data-placeholder="Cari kode atau kategori asset...">
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" @selected(old('asset_category_id', $asset->asset_category_id ?? null) == $c->id)>{{ $c->code }} —
                                    {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Nomor Seri</label><input name="serial_number"
                            class="form-control" value="{{ old('serial_number', $asset->serial_number ?? '') }}"></div>
                    <div class="col-md-4"><label class="form-label">Lokasi</label><input name="location"
                            class="form-control" value="{{ old('location', $asset->location ?? '') }}"></div>
                    <div class="col-md-4"><label class="form-label">Penanggung Jawab</label><input name="responsible_person"
                            class="form-control" value="{{ old('responsible_person', $asset->responsible_person ?? '') }}">
                    </div>
                    <div class="col-md-3"><label class="form-label">Kondisi *</label><select name="condition"
                            class="form-select">
                            @foreach (['BAIK', 'PERLU_SERVIS', 'RUSAK'] as $v)
                                <option @selected(old('condition', $asset->condition ?? 'BAIK') === $v)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">Tanggal Perolehan *</label><input required
                            type="date" name="acquisition_date" class="form-control"
                            value="{{ old('acquisition_date', isset($asset) ? $asset->acquisition_date->format('Y-m-d') : today()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3"><label class="form-label">Jenis Perolehan *</label><select name="acquisition_type"
                            class="form-select">
                            @foreach (['OPENING_BALANCE' => 'Saldo Awal', 'CASH' => 'Tunai', 'CREDIT' => 'Hutang', 'GRANT' => 'Hibah', 'CORRECTION' => 'Koreksi'] as $k => $v)
                                <option value="{{ $k }}" @selected(old('acquisition_type', $asset->acquisition_type ?? null) === $k)>{{ $v }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label class="form-label">COA Lawan Perolehan *</label><select required
                            name="acquisition_credit_coa_id" class="form-select" data-app-picker
                            data-placeholder="Cari kode atau nama akun...">
                            <option value="">Pilih akun</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}" @selected(old('acquisition_credit_coa_id', $asset->acquisition_credit_coa_id ?? null) == $a->id)>{{ $a->kode_akun }} —
                                    {{ $a->nama_akun }}</option>
                            @endforeach
                        </select></div>
                    @foreach ([['acquisition_cost', 'Harga Perolehan'], ['residual_value', 'Nilai Residu'], ['opening_accumulated_depreciation', 'Akumulasi Penyusutan Awal']] as [$n, $l])
                        <div class="col-md-4"><label class="form-label">{{ $l }} *</label><input required
                                type="number" min="0" step=".01" name="{{ $n }}"
                                class="form-control" value="{{ old($n, $asset->$n ?? 0) }}"></div>
                    @endforeach
                    <div class="col-md-4"><label class="form-label">Masa Manfaat (bulan)</label><input type="number"
                            min="1" name="useful_life_months" class="form-control"
                            value="{{ old('useful_life_months', $asset->useful_life_months ?? '') }}"></div>
                    <div class="col-md-4"><label class="form-label">Tanggal Mulai Penyusutan</label><input type="date"
                            name="depreciation_start_date" class="form-control"
                            value="{{ old('depreciation_start_date', isset($asset) && $asset->depreciation_start_date ? $asset->depreciation_start_date->format('Y-m-d') : '') }}"><small
                            class="text-muted">Disiapkan untuk standar mendatang; boleh kosong.</small></div>
                    <div class="col-12"><label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control">{{ old('notes', $asset->notes ?? '') }}</textarea>
                    </div>
                </div>
                <div class="card-footer bg-white text-end"><a href="{{ route('assets.index') }}"
                        class="btn btn-light">Batal</a><button class="btn btn-primary">Simpan & Posting</button></div>
            </form>
        </div>
    </div>
@endsection
