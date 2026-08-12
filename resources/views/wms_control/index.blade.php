@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4>WMS Control Center</h4>
                    <p class="text-muted mb-0">Traceability, execution, costing, dan planning dalam satu standar transaksi.
                    </p>
                </div>
                @if (auth()->user()->isWarehouseOperator() || auth()->user()->isSuperAdmin())
                    <form method="POST" action="{{ route('wms-control.replenishment') }}">@csrf<button
                            class="btn btn-primary">Hitung Replenishment</button></form>
                @endif
            </div>
            @include('warehouse_partials.alerts')

            <div class="row g-3 mb-4">
                @foreach ([['Lokasi', $locations->count(), 'fa-location-dot'], ['Lot aktif', $lots->count(), 'fa-barcode'], ['Reservasi', $reservations->where('status', 'ACTIVE')->count(), 'fa-lock'], ['Saran restock', $suggestions->count(), 'fa-arrow-trend-up']] as [$label, $value, $icon])
                    <div class="col-md-3">
                        <div class="card card-body">
                            <div class="d-flex justify-content-between"><span>{{ $label }}</span><i
                                    class="fa-solid {{ $icon }}"></i></div>
                            <h3 class="mb-0">{{ $value }}</h3>
                        </div>
                    </div>
                @endforeach
            </div>

            @if (auth()->user()->isWarehouseOperator() || auth()->user()->isSuperAdmin())
                <div class="row g-3 mb-4">
                    <div class="col-lg-6">
                        <div class="card card-body">
                            <h5>Tambah Bin / Lokasi</h5>
                            <form method="POST" action="{{ route('wms-control.locations.store') }}" class="row g-2">@csrf
                                <div class="col-md-4"><select name="gudang_id" class="form-select" required>
                                        <option value="">Gudang</option>
                                        @foreach ($gudangs as $g)
                                            <option value="{{ $g->id }}">{{ $g->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4"><input name="code" class="form-control" placeholder="Kode bin"
                                        required></div>
                                <div class="col-md-4"><input name="name" class="form-control" placeholder="Nama lokasi"
                                        required></div>
                                <div class="col-md-4"><select name="type" class="form-select" required>
                                        @foreach (['RECEIVING', 'QC', 'STORAGE', 'PICKING', 'TRANSIT', 'DAMAGED'] as $t)
                                            <option>{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6"><input name="zone" class="form-control"
                                        placeholder="Zona / aisle / rack"></div>
                                <div class="col-md-2"><button class="btn btn-success w-100">Simpan</button></div>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card card-body">
                            <h5>Tambah Serial Number</h5>
                            <form method="POST" action="{{ route('wms-control.serials.store') }}" class="row g-2">@csrf<div
                                    class="col-md-7"><select name="inventory_lot_id" class="form-select" required>
                                        @foreach ($lots as $lot)
                                            <option value="{{ $lot->id }}">{{ $lot->lot_number }} —
                                                {{ $lot->bahan->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3"><input name="serial_number" class="form-control"
                                        placeholder="Serial unik" required></div>
                                <div class="col-md-2"><button class="btn btn-success w-100">Tambah</button></div>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card card-body">
                            <h5>Tambah Lot</h5>
                            <form method="POST" action="{{ route('wms-control.lots.store') }}" class="row g-2">@csrf
                                <div class="col-md-5"><select name="bahan_id" class="form-select" required>
                                        <option value="">Bahan</option>
                                        @foreach ($bahans as $b)
                                            <option value="{{ $b->id }}">{{ $b->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3"><input name="lot_number" class="form-control" placeholder="Nomor lot"
                                        required></div>
                                <div class="col-md-3"><input type="date" name="expires_at" class="form-control"></div>
                                <div class="col-md-1"><button class="btn btn-success">+</button></div>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card card-body">
                            <h5>Reservasi Stok</h5>
                            <form method="POST" action="{{ route('wms-control.reservations.store') }}" class="row g-2">
                                @csrf
                                <div class="col-md-4"><select name="gudang_id" class="form-select" required>
                                        @foreach ($gudangs as $g)
                                            <option value="{{ $g->id }}">{{ $g->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4"><select name="bahan_id" class="form-select" required>
                                        @foreach ($bahans as $b)
                                            <option value="{{ $b->id }}">{{ $b->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2"><input type="number" step="0.000001" min="0.000001" name="quantity"
                                        class="form-control" required></div>
                                <div class="col-md-2"><button class="btn btn-success w-100">Reserve</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Lokasi Gudang</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Gudang</th>
                                        <th>Tipe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($locations as $l)
                                        <tr>
                                            <td>{{ $l->code }}</td>
                                            <td>{{ $l->gudang->nama }}</td>
                                            <td>{{ $l->type }}</td>
                                    </tr>@empty<tr>
                                            <td colspan="3" class="text-muted text-center">Belum ada bin.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Lot & Expiry</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Lot</th>
                                        <th>Bahan</th>
                                        <th>Expiry</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lots as $l)
                                        <tr
                                            class="{{ $l->expires_at && $l->expires_at->isPast() ? 'table-danger' : '' }}">
                                            <td>{{ $l->lot_number }}</td>
                                            <td>{{ $l->bahan->nama }}</td>
                                            <td>{{ $l->expires_at?->format('d-m-Y') ?: '-' }}</td>
                                            <td>{{ $l->blocked ? 'BLOCKED' : $l->quality_status }}</td>
                                            <td>
                                                @if (auth()->user()->isWarehouseOperator() || auth()->user()->isSuperAdmin())
                                                    <form method="POST"
                                                        action="{{ route('wms-control.lots.block', $l) }}">@csrf
                                                        @method('PATCH')<input type="hidden" name="blocked"
                                                            value="{{ $l->blocked ? 0 : 1 }}"><input type="hidden"
                                                            name="block_reason" value="Kontrol manual WMS"><button
                                                            class="btn btn-sm {{ $l->blocked ? 'btn-outline-success' : 'btn-outline-danger' }}">{{ $l->blocked ? 'Release' : 'Block' }}</button>
                                                    </form>
                                                @endif
                                            </td>
                                    </tr>@empty<tr>
                                            <td colspan="5" class="text-muted text-center">Belum ada lot.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @if (auth()->user()->isWarehouseOperator() || auth()->user()->isSuperAdmin())
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Receiving, QC & Putaway</h5>
                    </div>
                    <div class="card-body">
                        @forelse($pendingLpbs as $lpb)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between"><strong>{{ $lpb->id_lpb }}</strong><span
                                        class="badge bg-info">{{ $lpb->receiving_status }}</span></div>
                                @if ($lpb->receiving_status === 'RECEIVED')
                                    <form method="POST" action="{{ route('wms-control.lpb.inspect', $lpb) }}"
                                        class="mt-2">@csrf<div class="row g-2">
                                            @foreach ($lpb->details as $d)
                                                <div class="col-md-6"><label class="small">{{ $d->bahan->nama }} —
                                                        diterima {{ $d->jumlah_barang_diterima }}</label>
                                                    <div class="input-group"><span
                                                            class="input-group-text">Accepted</span><input type="number"
                                                            step="0.000001" min="0"
                                                            max="{{ $d->jumlah_barang_diterima }}"
                                                            name="decisions[{{ $d->id }}][accepted]"
                                                            value="{{ $d->jumlah_barang_diterima }}" class="form-control"
                                                            required><input name="decisions[{{ $d->id }}][reason]"
                                                            class="form-control" placeholder="Alasan reject"></div>
                                                </div>
                                            @endforeach
                                            <div class="col-12">
                                                <button class="btn btn-sm btn-primary">Selesaikan QC</button>
                                            </div>
                                        </div>
                                    </form>
                                @endif
                                @if ($locations->where('gudang_id', $lpb->gudang_id)->isNotEmpty())
                                    <form method="POST" action="{{ route('wms-control.lpb.putaway', $lpb) }}"
                                        class="d-flex gap-2 mt-2">@csrf<select name="warehouse_location_id"
                                            class="form-select form-select-sm" required>
                                            @foreach ($locations->where('gudang_id', $lpb->gudang_id) as $loc)
                                                <option value="{{ $loc->id }}">{{ $loc->code }} —
                                                    {{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-success">Putaway</button>
                                    </form>
                                @endif
                        </div>@empty<p class="text-muted mb-0">Tidak ada penerimaan yang menunggu putaway.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if ($invoices->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Three-Way Match: PO · LPB · Invoice</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Subtotal</th>
                                    <th>Match</th>
                                    <th>Issue</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoices as $i)
                                    <tr
                                        class="{{ $i->match_status === 'BLOCKED' ? 'table-danger' : ($i->match_status === 'WARNING' ? 'table-warning' : '') }}">
                                        <td>{{ $i->no_invoice }}</td>
                                        <td>Rp {{ number_format($i->sub_total, 2, ',', '.') }}</td>
                                        <td>{{ $i->match_status }}</td>
                                        <td>{{ count(data_get($i->match_summary, 'issues', [])) }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('wms-control.invoices.match', $i) }}">
                                                @csrf<button class="btn btn-sm btn-outline-primary">Match ulang</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Reservasi & Picking</h5>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Nomor</th>
                                <th>Gudang</th>
                                <th>Bahan</th>
                                <th>Qty</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reservations as $r)
                                <tr>
                                    <td>{{ $r->number }}</td>
                                    <td>{{ $r->gudang->nama }}</td>
                                    <td>{{ $r->bahan->nama }}</td>
                                    <td>{{ $r->quantity }}</td>
                                    <td>{{ $r->status }}</td>
                                    <td>
                                        @if ($r->status === 'ACTIVE')
                                            <form class="d-inline" method="POST"
                                                action="{{ route('wms-control.reservations.pick', $r) }}">@csrf<button
                                                    class="btn btn-sm btn-primary">Buat Pick</button></form>
                                            <form class="d-inline" method="POST"
                                                action="{{ route('wms-control.reservations.release', $r) }}">@csrf<button
                                                    class="btn btn-sm btn-outline-danger">Release</button></form>
                                        @endif
                                    </td>
                            </tr>@empty<tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada reservasi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($picks->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Picking Orders</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Status</th>
                                    <th>Baris</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($picks as $p)
                                    <tr>
                                        <td>{{ $p->number }}</td>
                                        <td>{{ $p->status }}</td>
                                        <td>{{ $p->lines->count() }}</td>
                                        <td>
                                            @if ($p->status === 'RELEASED')
                                                <form method="POST"
                                                    action="{{ route('wms-control.picking-orders.complete', $p) }}">
                                                    @csrf<button class="btn btn-sm btn-success">Selesaikan Pick</button>
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

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Replenishment</h5>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Gudang/Bahan</th>
                                <th>Available</th>
                                <th>Avg Usage/Hari</th>
                                <th>Saran</th>
                                <th>Prioritas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($suggestions as $s)
                                <tr class="{{ $s->priority === 'CRITICAL' ? 'table-danger' : '' }}">
                                    <td>#{{ $s->gudang_id }} / #{{ $s->bahan_id }}</td>
                                    <td>{{ $s->available_quantity }}</td>
                                    <td>{{ number_format($s->average_daily_usage, 3) }}</td>
                                    <td>{{ $s->suggested_quantity }}</td>
                                    <td>{{ $s->priority }}</td>
                            </tr>@empty<tr>
                                    <td colspan="5" class="text-center text-muted">Jalankan perhitungan untuk membuat
                                        saran.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if (auth()->user()->isAccounting() || auth()->user()->isSuperAdmin())
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Controlled Reversal</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <h6>LPB</h6>
                                @forelse($reversibleLpbs as $d)
                                    <form method="POST" action="{{ route('wms-control.lpb.reverse', $d) }}"
                                        class="input-group mb-2">@csrf<span
                                            class="input-group-text">{{ $d->id_lpb }}</span><input name="reason"
                                            class="form-control" minlength="10" placeholder="Alasan reversal (wajib)"
                                            required><button class="btn btn-outline-danger"
                                            onclick="return confirm('Balik seluruh stok dan jurnal LPB?')">Reverse</button>
                                </form>@empty<small class="text-muted">Tidak ada LPB yang eligible.</small>
                                @endforelse
                            </div>
                            <div class="col-lg-6">
                                <h6>NPK</h6>
                                @forelse($reversibleNpks as $d)
                                    <form method="POST" action="{{ route('wms-control.npk.reverse', $d) }}"
                                        class="input-group mb-2">@csrf<span
                                            class="input-group-text">{{ $d->kode }}</span><input name="reason"
                                            class="form-control" minlength="10" placeholder="Alasan reversal (wajib)"
                                            required><button class="btn btn-outline-danger"
                                            onclick="return confirm('Pulihkan FIFO, stok, dan balik jurnal NPK?')">Reverse</button>
                                </form>@empty<small class="text-muted">Tidak ada NPK yang eligible.</small>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Landed Cost</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('wms-control.landed-costs.store') }}" class="row g-2">
                            @csrf
                            <div class="col-md-2"><input type="date" name="date"
                                    value="{{ today()->toDateString() }}" class="form-control" required></div>
                            <div class="col-md-3"><input name="description" class="form-control"
                                    placeholder="Ongkir / bea / handling" required></div>
                            <div class="col-md-2"><input type="number" step="0.01" name="total_amount"
                                    class="form-control" placeholder="Nominal" required></div>
                            <div class="col-md-2"><select name="allocation_basis" class="form-select">
                                    <option value="VALUE">Berdasar nilai</option>
                                    <option value="QUANTITY">Berdasar qty</option>
                                </select></div>
                            <div class="col-md-3"><select name="credit_coa_id" class="form-select" required>
                                    @foreach ($creditAccounts as $a)
                                        <option value="{{ $a->id }}">{{ $a->kode_akun }} {{ $a->nama_akun }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12"><select name="layer_ids[]" class="form-select" multiple size="6"
                                    required>
                                    @foreach ($layers as $l)
                                        <option value="{{ $l->id }}">#{{ $l->id }} {{ $l->gudang->nama }}
                                            - {{ $l->bahan->nama }} | {{ $l->remaining_quantity }} × {{ $l->unit_cost }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12"><button class="btn btn-success">Buat & Alokasikan</button></div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Nomor</th>
                                    <th>Tanggal</th>
                                    <th>Nilai</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($landedCosts as $c)
                                    <tr>
                                        <td>{{ $c->number }}</td>
                                        <td>{{ $c->date->format('d-m-Y') }}</td>
                                        <td>Rp {{ number_format($c->total_amount, 2, ',', '.') }}</td>
                                        <td>{{ $c->status }}</td>
                                        <td>
                                            @if ($c->status === 'DRAFT')
                                                <form method="POST"
                                                    action="{{ route('wms-control.landed-costs.post', $c) }}">@csrf<button
                                                        class="btn btn-sm btn-primary">Post</button></form>
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
    </div>
@endsection
