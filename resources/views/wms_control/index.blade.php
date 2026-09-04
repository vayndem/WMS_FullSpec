@extends('layouts.app')
@section('content')
    <div class="content-page" x-data="{
        submitOrConfirm(event, message) {
            AppAlert.confirm(message).then(r => { if (r.isConfirmed) event.target.submit(); });
        },
    }">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold">WMS Control Center</h3>
                <p class="text-base-content/60">Traceability, execution, costing, dan planning dalam satu standar transaksi.</p>
            </div>
            @if (auth()->user()->isWarehouseOperator() || auth()->user()->isSuperAdmin())
                <form method="POST" action="{{ route('wms-control.replenishment') }}">
                    @csrf
                    <button class="btn btn-primary">Hitung Replenishment</button>
                </form>
            @endif
        </div>
        @include('warehouse_partials.alerts')

        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
            @foreach ([['Lokasi', $locations->count(), 'fa-location-dot'], ['Lot aktif', $lots->count(), 'fa-barcode'], ['Reservasi', $reservations->where('status', 'ACTIVE')->count(), 'fa-lock'], ['Saran restock', $suggestions->count(), 'fa-arrow-trend-up']] as [$label, $value, $icon])
                <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                    <div class="flex items-center justify-between text-base-content/60">
                        <span>{{ $label }}</span>
                        <i class="fa-solid {{ $icon }}"></i>
                    </div>
                    <p class="text-2xl font-bold">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        @if (auth()->user()->isWarehouseOperator() || auth()->user()->isSuperAdmin())
            <div class="mb-4 grid grid-cols-1 gap-3 lg:grid-cols-2">
                <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                    <h5 class="mb-3 font-bold">Tambah Bin / Lokasi</h5>
                    <form method="POST" action="{{ route('wms-control.locations.store') }}" class="grid grid-cols-1 gap-2 md:grid-cols-4">
                        @csrf
                        <select name="gudang_id" class="select select-bordered" required>
                            <option value="">Gudang</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}">{{ $g->nama }}</option>
                            @endforeach
                        </select>
                        <input name="code" class="input input-bordered" placeholder="Kode bin" required>
                        <input name="name" class="input input-bordered" placeholder="Nama lokasi" required>
                        <select name="type" class="select select-bordered" required>
                            @foreach (['RECEIVING', 'QC', 'STORAGE', 'PICKING', 'TRANSIT', 'DAMAGED'] as $t)
                                <option>{{ $t }}</option>
                            @endforeach
                        </select>
                        <input name="zone" class="input input-bordered md:col-span-3" placeholder="Zona / aisle / rack">
                        <button class="btn btn-success">Simpan</button>
                    </form>
                </div>
                <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                    <h5 class="mb-3 font-bold">Tambah Serial Number</h5>
                    <form method="POST" action="{{ route('wms-control.serials.store') }}" class="grid grid-cols-1 gap-2 md:grid-cols-4">
                        @csrf
                        <select name="inventory_lot_id" class="select select-bordered md:col-span-2" required>
                            @foreach ($lots as $lot)
                                <option value="{{ $lot->id }}">{{ $lot->lot_number }} — {{ $lot->bahan->nama }}</option>
                            @endforeach
                        </select>
                        <input name="serial_number" class="input input-bordered" placeholder="Serial unik" required>
                        <button class="btn btn-success">Tambah</button>
                    </form>
                </div>
                <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                    <h5 class="mb-3 font-bold">Tambah Lot</h5>
                    <form method="POST" action="{{ route('wms-control.lots.store') }}" class="grid grid-cols-1 gap-2 md:grid-cols-4">
                        @csrf
                        <select name="bahan_id" class="select select-bordered" required>
                            <option value="">Bahan</option>
                            @foreach ($bahans as $b)
                                <option value="{{ $b->id }}">{{ $b->nama }}</option>
                            @endforeach
                        </select>
                        <input name="lot_number" class="input input-bordered" placeholder="Nomor lot" required>
                        <input type="date" name="expires_at" class="input input-bordered">
                        <button class="btn btn-success">+</button>
                    </form>
                </div>
                <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                    <h5 class="mb-3 font-bold">Reservasi Stok</h5>
                    <form method="POST" action="{{ route('wms-control.reservations.store') }}" class="grid grid-cols-1 gap-2 md:grid-cols-4">
                        @csrf
                        <select name="gudang_id" class="select select-bordered" required>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}">{{ $g->nama }}</option>
                            @endforeach
                        </select>
                        <select name="bahan_id" class="select select-bordered" required>
                            @foreach ($bahans as $b)
                                <option value="{{ $b->id }}">{{ $b->nama }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.000001" min="0.000001" name="quantity" class="input input-bordered" required>
                        <button class="btn btn-success">Reserve</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="mb-4 grid grid-cols-1 gap-3 lg:grid-cols-2">
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4"><h5 class="font-bold">Lokasi Gudang</h5></div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Kode</th><th>Gudang</th><th>Tipe</th></tr></thead>
                        <tbody>
                            @forelse($locations as $l)
                                <tr>
                                    <td class="font-semibold">{{ $l->code }}</td>
                                    <td>{{ $l->gudang->nama }}</td>
                                    <td>{{ $l->type }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-base-content/50">Belum ada bin.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4"><h5 class="font-bold">Lot &amp; Expiry</h5></div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Lot</th><th>Bahan</th><th>Expiry</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse($lots as $l)
                                <tr class="{{ $l->expires_at && $l->expires_at->isPast() ? 'bg-error/10' : '' }}">
                                    <td class="font-semibold">{{ $l->lot_number }}</td>
                                    <td>{{ $l->bahan->nama }}</td>
                                    <td>{{ $l->expires_at?->format('d-m-Y') ?: '-' }}</td>
                                    <td>{{ $l->blocked ? 'BLOCKED' : $l->quality_status }}</td>
                                    <td>
                                        @if (auth()->user()->isWarehouseOperator() || auth()->user()->isSuperAdmin())
                                            <form method="POST" action="{{ route('wms-control.lots.block', $l) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="blocked" value="{{ $l->blocked ? 0 : 1 }}">
                                                <input type="hidden" name="block_reason" value="Kontrol manual WMS">
                                                <button class="btn btn-sm {{ $l->blocked ? 'btn-outline btn-success' : 'btn-outline btn-error' }}">{{ $l->blocked ? 'Release' : 'Block' }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-base-content/50">Belum ada lot.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if (auth()->user()->isWarehouseOperator() || auth()->user()->isSuperAdmin())
            <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4"><h5 class="font-bold">Receiving, QC &amp; Putaway</h5></div>
                <div class="p-4">
                    @forelse($pendingLpbs as $lpb)
                        <div class="mb-3 rounded-lg border border-base-300 p-3">
                            <div class="flex items-center justify-between">
                                <strong>{{ $lpb->id_lpb }}</strong>
                                <span class="badge badge-info">{{ $lpb->receiving_status }}</span>
                            </div>
                            @if ($lpb->receiving_status === 'RECEIVED')
                                <form method="POST" action="{{ route('wms-control.penerimaan-barang.inspect', $lpb) }}" class="mt-2">
                                    @csrf
                                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                        @foreach ($lpb->details as $d)
                                            <div>
                                                <label class="label"><span class="label-text text-sm">{{ $d->bahan->nama }} — diterima {{ $d->jumlah_barang_diterima }}</span></label>
                                                <div class="join w-full">
                                                    <span class="join-item btn btn-disabled btn-outline">Accepted</span>
                                                    <input type="number" step="0.000001" min="0" max="{{ $d->jumlah_barang_diterima }}"
                                                        name="decisions[{{ $d->id }}][accepted]" value="{{ $d->jumlah_barang_diterima }}"
                                                        class="input input-bordered join-item flex-1" required>
                                                    <input name="decisions[{{ $d->id }}][reason]" class="input input-bordered join-item flex-1" placeholder="Alasan reject">
                                                </div>
                                            </div>
                                        @endforeach
                                        <div class="md:col-span-2">
                                            <button class="btn btn-primary btn-sm">Selesaikan QC</button>
                                        </div>
                                    </div>
                                </form>
                            @endif
                            @if ($locations->where('gudang_id', $lpb->gudang_id)->isNotEmpty())
                                <form method="POST" action="{{ route('wms-control.penerimaan-barang.putaway', $lpb) }}" class="mt-2 flex gap-2">
                                    @csrf
                                    <select name="warehouse_location_id" class="select select-bordered select-sm" required>
                                        @foreach ($locations->where('gudang_id', $lpb->gudang_id) as $loc)
                                            <option value="{{ $loc->id }}">{{ $loc->code }} — {{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-success btn-sm">Putaway</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-base-content/50">Tidak ada penerimaan yang menunggu putaway.</p>
                    @endforelse
                </div>
            </div>
        @endif

        @if ($invoices->isNotEmpty())
            <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4"><h5 class="font-bold">Three-Way Match: PO &middot; Penerimaan Barang &middot; Invoice</h5></div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Invoice</th><th>Subtotal</th><th>Match</th><th>Issue</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($invoices as $i)
                                <tr class="{{ $i->match_status === 'BLOCKED' ? 'bg-error/10' : ($i->match_status === 'WARNING' ? 'bg-warning/10' : '') }}">
                                    <td class="font-semibold">{{ $i->no_invoice }}</td>
                                    <td>Rp {{ number_format($i->sub_total, 2, ',', '.') }}</td>
                                    <td>{{ $i->match_status }}</td>
                                    <td>{{ count(data_get($i->match_summary, 'issues', [])) }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('wms-control.invoices.match', $i) }}">
                                            @csrf
                                            <button class="btn btn-outline btn-primary btn-sm">Match ulang</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-4"><h5 class="font-bold">Reservasi &amp; Picking</h5></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Nomor</th><th>Gudang</th><th>Bahan</th><th>Qty</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse($reservations as $r)
                            <tr>
                                <td class="font-semibold">{{ $r->number }}</td>
                                <td>{{ $r->gudang->nama }}</td>
                                <td>{{ $r->bahan->nama }}</td>
                                <td>{{ $r->quantity }}</td>
                                <td>{{ $r->status }}</td>
                                <td>
                                    @if ($r->status === 'ACTIVE')
                                        <div class="flex gap-1">
                                            <form method="POST" action="{{ route('wms-control.reservations.pick', $r) }}">
                                                @csrf
                                                <button class="btn btn-primary btn-sm">Buat Pick</button>
                                            </form>
                                            <form method="POST" action="{{ route('wms-control.reservations.release', $r) }}">
                                                @csrf
                                                <button class="btn btn-outline btn-error btn-sm">Release</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-base-content/50">Belum ada reservasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($picks->isNotEmpty())
            <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4"><h5 class="font-bold">Picking Orders</h5></div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Nomor</th><th>Status</th><th>Baris</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($picks as $p)
                                <tr>
                                    <td class="font-semibold">{{ $p->number }}</td>
                                    <td>{{ $p->status }}</td>
                                    <td>{{ $p->lines->count() }}</td>
                                    <td>
                                        @if ($p->status === 'RELEASED')
                                            <form method="POST" action="{{ route('wms-control.picking-orders.complete', $p) }}">
                                                @csrf
                                                <button class="btn btn-success btn-sm">Selesaikan Pick</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-4"><h5 class="font-bold">Replenishment</h5></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Gudang/Bahan</th><th>Available</th><th>Avg Usage/Hari</th><th>Saran</th><th>Prioritas</th></tr></thead>
                    <tbody>
                        @forelse($suggestions as $s)
                            <tr class="{{ $s->priority === 'CRITICAL' ? 'bg-error/10' : '' }}">
                                <td>#{{ $s->gudang_id }} / #{{ $s->bahan_id }}</td>
                                <td>{{ $s->available_quantity }}</td>
                                <td>{{ number_format($s->average_daily_usage, 3) }}</td>
                                <td>{{ $s->suggested_quantity }}</td>
                                <td>{{ $s->priority }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-base-content/50">Jalankan perhitungan untuk membuat saran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (auth()->user()->isAccounting() || auth()->user()->isSuperAdmin())
            <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4"><h5 class="font-bold">Controlled Reversal</h5></div>
                <div class="grid grid-cols-1 gap-4 p-4 lg:grid-cols-2">
                    <div>
                        <h6 class="mb-2 font-semibold">Penerimaan Barang</h6>
                        @forelse($reversibleLpbs as $d)
                            <form method="POST" action="{{ route('wms-control.penerimaan-barang.reverse', $d) }}"
                                @submit.prevent="submitOrConfirm($event, 'Balik seluruh stok dan jurnal penerimaan barang?')" class="join mb-2 w-full">
                                @csrf
                                <span class="join-item btn btn-disabled btn-outline">{{ $d->id_lpb }}</span>
                                <input name="reason" class="input input-bordered join-item flex-1" minlength="10" placeholder="Alasan reversal (wajib)" required>
                                <button class="join-item btn btn-outline btn-error">Reverse</button>
                            </form>
                        @empty
                            <small class="text-base-content/50">Tidak ada penerimaan barang yang eligible.</small>
                        @endforelse
                    </div>
                    <div>
                        <h6 class="mb-2 font-semibold">NPK</h6>
                        @forelse($reversibleNpks as $d)
                            <form method="POST" action="{{ route('wms-control.npk.reverse', $d) }}"
                                @submit.prevent="submitOrConfirm($event, 'Pulihkan FIFO, stok, dan balik jurnal NPK?')" class="join mb-2 w-full">
                                @csrf
                                <span class="join-item btn btn-disabled btn-outline">{{ $d->kode }}</span>
                                <input name="reason" class="input input-bordered join-item flex-1" minlength="10" placeholder="Alasan reversal (wajib)" required>
                                <button class="join-item btn btn-outline btn-error">Reverse</button>
                            </form>
                        @empty
                            <small class="text-base-content/50">Tidak ada NPK yang eligible.</small>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="card mb-4 border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-4"><h5 class="font-bold">Landed Cost</h5></div>
                <div class="p-4">
                    <form method="POST" action="{{ route('wms-control.landed-costs.store') }}" class="grid grid-cols-1 gap-2 md:grid-cols-6">
                        @csrf
                        <input type="date" name="date" value="{{ today()->toDateString() }}" class="input input-bordered md:col-span-1" required>
                        <input name="description" class="input input-bordered md:col-span-2" placeholder="Ongkir / bea / handling" required>
                        <input type="number" step="0.01" name="total_amount" data-money-input class="input input-bordered" placeholder="Nominal" required>
                        <select name="allocation_basis" class="select select-bordered">
                            <option value="VALUE">Berdasar nilai</option>
                            <option value="QUANTITY">Berdasar qty</option>
                        </select>
                        <select name="credit_coa_id" class="select select-bordered" required>
                            @foreach ($creditAccounts as $a)
                                <option value="{{ $a->id }}">{{ $a->kode_akun }} {{ $a->nama_akun }}</option>
                            @endforeach
                        </select>
                        <select name="layer_ids[]" class="select select-bordered md:col-span-6" multiple size="6" required>
                            @foreach ($layers as $l)
                                <option value="{{ $l->id }}">#{{ $l->id }} {{ $l->gudang->nama }} - {{ $l->bahan->nama }} | {{ $l->remaining_quantity }} × {{ $l->unit_cost }}</option>
                            @endforeach
                        </select>
                        <div class="md:col-span-6">
                            <button class="btn btn-success">Buat &amp; Alokasikan</button>
                        </div>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Nomor</th><th>Tanggal</th><th>Nilai</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($landedCosts as $c)
                                <tr>
                                    <td class="font-semibold">{{ $c->number }}</td>
                                    <td>{{ $c->date->format('d-m-Y') }}</td>
                                    <td>Rp {{ number_format($c->total_amount, 2, ',', '.') }}</td>
                                    <td>{{ $c->status }}</td>
                                    <td>
                                        @if ($c->status === 'DRAFT')
                                            <form method="POST" action="{{ route('wms-control.landed-costs.post', $c) }}">
                                                @csrf
                                                <button class="btn btn-primary btn-sm">Post</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
