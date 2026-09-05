@extends('layouts.app')
@section('content')
    <div class="content-page" x-data="{
        submitting: false,
        async confirmDispose(event) {
            const form = event.target;
            if (form.dataset.confirmed) return;
            const result = await AppAlert.confirm('Posting pelepasan aset? Tindakan ini tidak dapat diedit kembali.');
            if (result.isConfirmed) {
                form.dataset.confirmed = '1';
                form.submit();
            }
        },
    }">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold">{{ $asset->nomor_aset }} — {{ $asset->name }}</h3>
                <p class="text-base-content/60">{{ $asset->category->name }} · {{ $asset->status }}</p>
            </div>
            @can('update', $asset)
                <a href="{{ route('aset.edit', $asset) }}" class="btn btn-outline btn-primary">Edit</a>
            @endcan
        </div>
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="card border border-base-300 bg-base-100 shadow-sm lg:col-span-5">
                <div class="card-body p-4">
                    <h5 class="font-bold">Identitas Aset</h5>
                    <dl class="mt-2 grid grid-cols-2 gap-y-2 text-sm">
                        @foreach (['Nomor Seri' => $asset->serial_number, 'Lokasi' => $asset->location, 'Penanggung Jawab' => $asset->responsible_person, 'Kondisi' => $asset->condition, 'Tanggal Perolehan' => $asset->acquisition_date->format('d-m-Y'), 'Jenis Perolehan' => $asset->acquisition_type] as $k => $v)
                            <dt class="text-base-content/50">{{ $k }}</dt>
                            <dd>{{ $v ?: '-' }}</dd>
                        @endforeach
                    </dl>
                </div>
            </div>
            @if ($financial)
                <div class="card border border-base-300 bg-base-100 shadow-sm lg:col-span-7">
                    <div class="card-body p-4">
                        <h5 class="font-bold">Nilai Finansial</h5>
                        <div class="mt-2 grid grid-cols-2 gap-3">
                            @foreach ([['Harga perolehan', $asset->acquisition_cost], ['Akumulasi penyusutan', $asset->accumulated_depreciation], ['Nilai buku', $asset->book_value], ['Nilai residu', $asset->residual_value], ['Saran garis lurus/bulan', $asset->suggestedMonthlyDepreciation()]] as [$k, $v])
                                <div>
                                    <p class="text-sm text-base-content/50">{{ $k }}</p>
                                    <p class="text-lg font-bold">Rp {{ number_format($v, 0, ',', '.') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @can('depreciate', $asset)
            <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-2">
                <form method="post" action="{{ route('aset.depreciate', $asset) }}" class="card border border-base-300 bg-base-100 shadow-sm">
                    @csrf
                    <div class="p-4">
                        <h5 class="font-bold">Posting Penyusutan Manual</h5>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <div class="form-control">
                                <label class="label"><span class="label-text">Tanggal</span></label>
                                <input required type="date" name="posting_date" value="{{ today()->format('Y-m-d') }}" class="input input-bordered">
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text">Nominal</span></label>
                                <input type="number" min=".01" step=".01" name="amount" data-money-input class="input input-bordered" placeholder="Kosongkan untuk saran otomatis: Rp {{ number_format($asset->suggestedMonthlyDepreciation(), 0, ',', '.') }}">
                            </div>
                            <div class="form-control col-span-2">
                                <label class="label"><span class="label-text">Periode/Keterangan</span></label>
                                <input required name="period_label" class="input input-bordered" placeholder="Contoh: Evaluasi Juli 2026">
                            </div>
                            <div class="form-control col-span-2">
                                <label class="label"><span class="label-text">Alasan</span></label>
                                <textarea name="reason" class="textarea textarea-bordered"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-base-300 p-4">
                        <button class="btn btn-primary">Posting Penyusutan</button>
                    </div>
                </form>
                <form method="post" action="{{ route('aset.dispose', $asset) }}" class="card border border-base-300 bg-base-100 shadow-sm" @submit="confirmDispose($event)">
                    @csrf
                    <div class="p-4">
                        <h5 class="font-bold">Penjualan / Penghapusan</h5>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <input required type="date" name="disposal_date" value="{{ today()->format('Y-m-d') }}" class="input input-bordered">
                            <select name="disposal_type" class="select select-bordered">
                                <option value="SALE">Dijual</option>
                                <option value="WRITE_OFF">Dihapus</option>
                            </select>
                            <input type="number" min="0" step=".01" name="proceeds" class="input input-bordered" placeholder="Hasil penjualan">
                            <select name="cash_bank_coa_id" class="select select-bordered">
                                <option value="">Kas/Bank</option>
                                @foreach ($cashBanks as $a)
                                    <option value="{{ $a->id }}">{{ $a->kode_akun }} — {{ $a->nama_akun }}</option>
                                @endforeach
                            </select>
                            <textarea required name="reason" class="textarea textarea-bordered col-span-2" placeholder="Alasan pelepasan"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-base-300 p-4">
                        <button class="btn btn-error">Posting Pelepasan</button>
                    </div>
                </form>
            </div>
        @endcan

        <div class="card mt-3 border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body p-4">
                <h5 class="font-bold">Riwayat Penyusutan</h5>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Periode</th>
                                @if ($financial)
                                    <th class="text-end">Nominal</th>
                                    <th class="text-end">Nilai Buku</th>
                                @endif
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($asset->depreciations as $d)
                                <tr>
                                    <td>{{ $d->posting_date->format('d-m-Y') }}</td>
                                    <td>{{ $d->period_label }}</td>
                                    @if ($financial)
                                        <td class="text-end">Rp {{ number_format($d->amount, 0, ',', '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($d->book_value_after, 0, ',', '.') }}</td>
                                    @endif
                                    <td>{{ $d->reason ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-base-content/50">Belum ada penyusutan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
