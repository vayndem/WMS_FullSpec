@extends('layouts.app')

@section('content')
    <div class="content-page" x-data="{ items: [{ deskripsi: '', jumlah: 1 }] }">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Barang Keluar ke Vendor &amp; Barang Titipan</h3>
            <p class="text-base-content/60">
                Catatan kustodian: barang kita yang keluar untuk diperbaiki, dan barang pinjaman vendor selama perbaikan.
                <strong>Keduanya tidak membuat jurnal</strong> — kepemilikan tidak berpindah.
            </p>
        </div>

        @if (session('success'))
            <div role="alert" class="alert alert-success mb-4 shadow-sm">
                <i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div role="alert" class="alert alert-error mb-4 shadow-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>@foreach ($errors->all() as $pesan)<p class="text-sm">{{ $pesan }}</p>@endforeach</div>
            </div>
        @endif

        @if ($terlambat['keluar']->isNotEmpty() || $terlambat['titipan']->isNotEmpty())
            <div role="alert" class="alert alert-warning mb-4 shadow-sm">
                <i class="fa-solid fa-clock"></i>
                <span>
                    {{ $terlambat['keluar']->count() }} gate pass dan {{ $terlambat['titipan']->count() }} barang titipan
                    sudah melewati estimasi kembali.
                </span>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4">
                    <h5 class="font-bold"><i class="fa-solid fa-right-from-bracket text-primary"></i> Keluarkan Barang ke Vendor</h5>
                </div>
                <form action="{{ route('pengeluaran-barang.store') }}" method="POST" class="p-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Tanggal</span></label>
                            <input type="date" name="tanggal" value="{{ today()->toDateString() }}" class="input input-bordered input-sm" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Vendor Tujuan</span></label>
                            <select name="supplier_id" class="select select-bordered select-sm" data-app-picker required>
                                <option value="">Pilih vendor...</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Keperluan</span></label>
                            <select name="keperluan" class="select select-bordered select-sm" required>
                                @foreach ($keperluan as $kode => $label)
                                    <option value="{{ $kode }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Estimasi Kembali</span></label>
                            <input type="date" name="estimasi_kembali" class="input input-bordered input-sm">
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="font-semibold">Barang yang Dikeluarkan</span>
                            <button type="button" class="btn btn-ghost btn-xs" @click="items.push({ deskripsi: '', jumlah: 1 })">
                                <i class="fa-solid fa-plus"></i> Tambah
                            </button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(baris, index) in items" :key="index">
                                <div class="grid grid-cols-1 gap-2 rounded-box border border-base-300 p-2 sm:grid-cols-12">
                                    <select :name="`items[${index}][aset_id]`" class="select select-bordered select-xs sm:col-span-4">
                                        <option value="">Bukan aset terdaftar</option>
                                        @foreach ($asets as $aset)
                                            <option value="{{ $aset->id }}">{{ $aset->nomor_aset }} — {{ $aset->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" :name="`items[${index}][deskripsi]`" x-model="baris.deskripsi"
                                        class="input input-bordered input-xs sm:col-span-4" placeholder="Deskripsi barang" required>
                                    <input type="text" :name="`items[${index}][nomor_seri]`"
                                        class="input input-bordered input-xs sm:col-span-2" placeholder="No. seri">
                                    <input type="number" step="0.000001" :name="`items[${index}][jumlah]`" x-model="baris.jumlah"
                                        class="input input-bordered input-xs sm:col-span-1">
                                    <button type="button" class="btn btn-ghost btn-xs text-error sm:col-span-1"
                                        x-show="items.length > 1" @click="items.splice(index, 1)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-3 flex justify-end">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Buat Gate Pass</button>
                    </div>
                </form>
            </div>

            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4">
                    <h5 class="font-bold"><i class="fa-solid fa-handshake-angle text-warning"></i> Terima Barang Titipan Vendor</h5>
                </div>
                <form action="{{ route('barang-titipan.store') }}" method="POST" class="p-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Vendor Pemilik</span></label>
                            <select name="supplier_id" class="select select-bordered select-sm" data-app-picker required>
                                <option value="">Pilih vendor...</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Pengganti untuk Gate Pass</span></label>
                            <select name="pengeluaran_id" class="select select-bordered select-sm">
                                <option value="">Tidak terkait</option>
                                @foreach ($pengeluaran as $gp)
                                    <option value="{{ $gp->id }}">{{ $gp->nomor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control sm:col-span-2">
                            <label class="label"><span class="label-text font-semibold">Deskripsi Barang</span></label>
                            <input type="text" name="deskripsi" class="input input-bordered input-sm" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Tanggal Terima</span></label>
                            <input type="date" name="tanggal_terima" value="{{ today()->toDateString() }}" class="input input-bordered input-sm" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Estimasi Kembali</span></label>
                            <input type="date" name="estimasi_kembali" class="input input-bordered input-sm">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">No. Seri</span></label>
                            <input type="text" name="nomor_seri" class="input input-bordered input-sm">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Nilai Taksiran (memo)</span></label>
                            <input type="number" step="0.01" name="nilai_taksiran" class="input input-bordered input-sm">
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs text-base-content/60">
                            Nilai taksiran hanya memo — barang ini milik vendor, tidak masuk persediaan maupun aset.
                        </span>
                        <button type="submit" class="btn btn-warning btn-sm"><i class="fa-solid fa-inbox"></i> Catat Titipan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4 border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-4">
                <h5 class="font-bold"><i class="fa-solid fa-truck-arrow-right text-primary"></i> Gate Pass</h5>
            </div>
            <div class="divide-y divide-base-300">
                @forelse ($pengeluaran as $gp)
                    <div class="p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold">
                                    {{ $gp->nomor }}
                                    @if ($gp->terlambat())
                                        <span class="badge badge-warning badge-sm">terlambat</span>
                                    @endif
                                </p>
                                <p class="text-xs text-base-content/60">
                                    {{ $gp->supplier->nama ?? '-' }} &middot; {{ $gp->keperluan }} &middot;
                                    {{ optional($gp->tanggal)->format('d-m-Y') }}
                                    @if ($gp->estimasi_kembali) &middot; estimasi kembali {{ $gp->estimasi_kembali->format('d-m-Y') }} @endif
                                </p>
                            </div>
                            <span class="badge badge-sm {{ ['DRAFT' => 'badge-ghost', 'DI_VENDOR' => 'badge-info', 'SEBAGIAN_KEMBALI' => 'badge-warning', 'SELESAI' => 'badge-success', 'DIBATALKAN' => 'badge-error'][$gp->status] ?? 'badge-ghost' }}">
                                {{ $gp->status }}
                            </span>
                        </div>

                        <ul class="mt-2 space-y-1 text-sm">
                            @foreach ($gp->details as $detail)
                                <li class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="min-w-0">
                                        {{ $detail->aset->nomor_aset ?? '' }} {{ $detail->deskripsi }}
                                        <span class="text-xs text-base-content/50">x{{ rtrim(rtrim(number_format((float) $detail->jumlah, 4, ',', '.'), '0'), ',') }}</span>
                                    </span>
                                    <span class="badge badge-xs {{ $detail->status === 'KEMBALI' ? 'badge-success' : ($detail->status === 'TIDAK_KEMBALI' ? 'badge-error' : 'badge-ghost') }}">
                                        {{ $detail->status }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($gp->status === 'DRAFT')
                                <form action="{{ route('pengeluaran-barang.kirim', $gp) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-xs"><i class="fa-solid fa-paper-plane"></i> Kirim ke Vendor</button>
                                </form>
                            @endif
                            @if (in_array($gp->status, ['DI_VENDOR', 'SEBAGIAN_KEMBALI'], true))
                                <form action="{{ route('pengeluaran-barang.terima', $gp) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="date" name="tanggal_kembali" value="{{ today()->toDateString() }}" class="input input-bordered input-xs w-32">
                                    @foreach ($gp->details->where('status', 'DI_VENDOR') as $detail)
                                        <label class="label cursor-pointer gap-1 py-0">
                                            <input type="checkbox" name="baris[{{ $detail->id }}][status]" value="KEMBALI" class="checkbox checkbox-xs">
                                            <span class="label-text text-xs">{{ \Illuminate\Support\Str::limit($detail->deskripsi, 18) }}</span>
                                        </label>
                                    @endforeach
                                    <button type="submit" class="btn btn-success btn-xs"><i class="fa-solid fa-check"></i> Terima Kembali</button>
                                </form>
                            @endif
                            @if (!in_array($gp->status, ['SELESAI', 'DIBATALKAN'], true))
                                <form action="{{ route('pengeluaran-barang.destroy', $gp) }}" method="POST"
                                    x-data @submit.prevent="AppAlert.confirm('Batalkan gate pass ini?').then(r => r.isConfirmed && $el.submit())">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-xmark"></i> Batal</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center text-base-content/50">Belum ada barang yang dikeluarkan ke vendor.</div>
                @endforelse
            </div>
        </div>

        <div class="card mt-4 border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-4">
                <h5 class="font-bold"><i class="fa-solid fa-box-open text-warning"></i> Barang Titipan Vendor</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Nomor</th><th>Vendor</th><th>Barang</th><th>Diterima</th>
                            <th>Estimasi Kembali</th><th>Status</th><th class="text-center">Tutup</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($titipan as $row)
                            <tr>
                                <td class="font-semibold">{{ $row->nomor }}</td>
                                <td>{{ $row->supplier->nama ?? '-' }}</td>
                                <td>{{ $row->deskripsi }}</td>
                                <td>{{ optional($row->tanggal_terima)->format('d-m-Y') }}</td>
                                <td>
                                    {{ optional($row->estimasi_kembali)->format('d-m-Y') ?: '-' }}
                                    @if ($row->terlambat())<span class="badge badge-warning badge-xs">lewat</span>@endif
                                </td>
                                <td><span class="badge badge-sm {{ $row->status === 'DITERIMA' ? 'badge-info' : 'badge-ghost' }}">{{ $row->status }}</span></td>
                                <td>
                                    @if ($row->status === 'DITERIMA')
                                        <form action="{{ route('barang-titipan.selesai', $row) }}" method="POST" class="flex items-center justify-center gap-1">
                                            @csrf
                                            <select name="status" class="select select-bordered select-xs">
                                                <option value="DIKEMBALIKAN">Dikembalikan</option>
                                                <option value="DIBELI">Dibeli</option>
                                                <option value="HILANG">Hilang</option>
                                            </select>
                                            <button type="submit" class="btn btn-ghost btn-xs"><i class="fa-solid fa-check"></i></button>
                                        </form>
                                    @else
                                        <span class="text-xs text-base-content/40">{{ optional($row->tanggal_kembali)->format('d-m-Y') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-base-content/50">Belum ada barang titipan vendor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
