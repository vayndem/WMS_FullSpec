@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <h4>{{ $asset->asset_number }} — {{ $asset->name }}</h4>
                    <p class="text-muted mb-0">{{ $asset->category->name }} · {{ $asset->status }}</p>
                </div>
                @can('update', $asset)
                    <a href="{{ route('assets.edit', $asset) }}" class="btn btn-outline-primary">Edit</a>
                @endcan
            </div>
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5>Identitas Asset</h5>
                            <dl class="row mb-0">
                                @foreach (['Nomor Seri' => $asset->serial_number, 'Lokasi' => $asset->location, 'Penanggung Jawab' => $asset->responsible_person, 'Kondisi' => $asset->condition, 'Tanggal Perolehan' => $asset->acquisition_date->format('d-m-Y'), 'Jenis Perolehan' => $asset->acquisition_type] as $k => $v)
                                    <dt class="col-5 text-muted">{{ $k }}</dt>
                                    <dd class="col-7">{{ $v ?: '-' }}</dd>
                                @endforeach
                            </dl>
                        </div>
                    </div>
                </div>
                @if ($financial)
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5>Nilai Finansial</h5>
                                <div class="row g-3">
                                    @foreach ([['Harga perolehan', $asset->acquisition_cost], ['Akumulasi penyusutan', $asset->accumulated_depreciation], ['Nilai buku', $asset->book_value], ['Nilai residu', $asset->residual_value], ['Saran garis lurus/bulan', $asset->suggestedMonthlyDepreciation()]] as [$k, $v])
                                        <div class="col-6"><small class="text-muted">{{ $k }}</small>
                                            <div class="fw-bold fs-5">Rp {{ number_format($v, 0, ',', '.') }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            @can('depreciate', $asset)
                <div class="row g-3 mt-1">
                    <div class="col-lg-6">
                        <form method="post" action="{{ route('assets.depreciate', $asset) }}" class="card border-0 shadow-sm">
                            @csrf<div class="card-body">
                                <h5>Posting Penyusutan Manual</h5>
                                <div class="row g-2">
                                    <div class="col-6"><label class="form-label">Tanggal</label><input required type="date"
                                            name="posting_date" value="{{ today()->format('Y-m-d') }}" class="form-control">
                                    </div>
                                    <div class="col-6"><label class="form-label">Nominal</label><input required type="number"
                                            min=".01" step=".01" name="amount" class="form-control"></div>
                                    <div class="col-12"><label class="form-label">Periode/Keterangan</label><input required
                                            name="period_label" class="form-control" placeholder="Contoh: Evaluasi Juli 2026">
                                    </div>
                                    <div class="col-12"><label class="form-label">Alasan</label>
                                        <textarea name="reason" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white text-end"><button class="btn btn-primary">Posting
                                    Penyusutan</button></div>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <form method="post" action="{{ route('assets.dispose', $asset) }}" class="card border-0 shadow-sm">
                            @csrf<div class="card-body">
                                <h5>Penjualan / Penghapusan</h5>
                                <div class="row g-2">
                                    <div class="col-6"><input required type="date" name="disposal_date"
                                            value="{{ today()->format('Y-m-d') }}" class="form-control"></div>
                                    <div class="col-6"><select name="disposal_type" class="form-select">
                                            <option value="SALE">Dijual</option>
                                            <option value="WRITE_OFF">Dihapus</option>
                                        </select></div>
                                    <div class="col-6"><input type="number" min="0" step=".01" name="proceeds"
                                            class="form-control" placeholder="Hasil penjualan"></div>
                                    <div class="col-6"><select name="cash_bank_coa_id" class="form-select">
                                            <option value="">Kas/Bank</option>
                                            @foreach ($cashBanks as $a)
                                                <option value="{{ $a->id }}">{{ $a->kode_akun }} — {{ $a->nama_akun }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <textarea required name="reason" class="form-control" placeholder="Alasan pelepasan"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white text-end"><button class="btn btn-danger">Posting Pelepasan</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h5>Riwayat Penyusutan</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Periode</th>
                                    @if ($financial)
                                        <th class="text-end">Nominal</th>
                                        <th class="text-end">Nilai Buku</th>
                                    @endif
                                    <th>
                                        Alasan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($asset->depreciations as $d)
                                    <tr>
                                        <td>{{ $d->posting_date->format('d-m-Y') }}</td>
                                        <td>{{ $d->period_label }}</td>
                                        @if ($financial)
                                            <td class="text-end">Rp {{ number_format($d->amount, 0, ',', '.') }}</td>
                                            <td class="text-end">Rp {{ number_format($d->book_value_after, 0, ',', '.') }}
                                            </td>
                                        @endif
                                        <td>
                                            {{ $d->reason ?: '-' }}</td>
                                </tr>@empty<tr>
                                        <td colspan="5" class="text-center text-muted">Belum ada penyusutan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
</div>@endsection
@push('scripts')
    <script>
        document.querySelector('form[action*="/dispose"]')?.addEventListener('submit', async function(e) {
            if (this.dataset.confirmed) return;
            e.preventDefault();
            const result = await AppAlert.confirm(
                'Posting pelepasan asset? Tindakan ini tidak dapat diedit kembali.');
            if (result.isConfirmed) {
                this.dataset.confirmed = '1';
                this.submit()
            }
        });
    </script>
@endpush
