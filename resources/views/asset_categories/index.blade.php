@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="mb-3">
                <h4>Kategori & Mapping Asset</h4>
                <p class="text-muted">Mapping jurnal perolehan, penyusutan, dan pelepasan asset.</p>
            </div>
            @can('create', App\Models\AssetCategory::class)
                <form method="post" action="{{ route('asset-categories.store') }}" class="card border-0 shadow-sm mb-3">
                    <div class="card-body row g-2">@csrf
                        <div class="col-md-2"><input required name="code" class="form-control" placeholder="Kode"></div>
                        <div class="col-md-3"><input required name="name" class="form-control" placeholder="Nama kategori">
                        </div>
                        @foreach ([['asset_coa_id', 'COA Asset'], ['accumulated_depreciation_coa_id', 'Akumulasi'], ['depreciation_expense_coa_id', 'Beban Penyusutan'], ['disposal_gain_coa_id', 'Untung Pelepasan'], ['disposal_loss_coa_id', 'Rugi Pelepasan']] as [$n, $l])
                            <div class="col-md-3"><select required name="{{ $n }}" class="form-select"
                                    data-app-picker data-placeholder="Cari akun...">
                                    <option value="">{{ $l }}</option>
                                    @foreach ($accounts as $a)
                                        <option value="{{ $a->id }}">{{ $a->kode_akun }} — {{ $a->nama_akun }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                        <input type="hidden" name="is_active" value="1">
                        <div class="col-12 text-end"><button class="btn btn-primary">Tambah Kategori</button></div>
                    </div>
                </form>
            @endcan
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>COA Asset</th>
                                <th>Akumulasi</th>
                                <th>Beban</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $c)
                                <tr>
                                    <td>{{ $c->code }}</td>
                                    <td>{{ $c->name }}</td>
                                    <td>{{ $c->assetAccount->kode_akun }} — {{ $c->assetAccount->nama_akun }}</td>
                                    <td>{{ $c->accumulatedAccount->kode_akun }}</td>
                                    <td>{{ $c->expenseAccount->kode_akun }}</td>
                                    <td><span
                                            class="badge bg-{{ $c->is_active ? 'success' : 'secondary' }}">{{ $c->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </td>
                                    <td class="text-end">
                                        @can('update', $c)
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#editCategory{{ $c->id }}">Edit</button>
                                            @endcan @can('delete', $c)
                                            <form method="post" action="{{ route('asset-categories.destroy', $c) }}"
                                                class="d-inline swal-confirm-form" data-confirm="Hapus kategori asset ini?">
                                                @csrf @method('DELETE')<button
                                                    class="btn btn-sm btn-outline-danger">Hapus</button></form>
                                        @endcan
                                    </td>
                                </tr>
                                @can('update', $c)
                                    <div class="modal fade" id="editCategory{{ $c->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <form method="post" action="{{ route('asset-categories.update', $c) }}"
                                                class="modal-content">@csrf @method('PUT')<div class="modal-header">
                                                    <h5>Edit {{ $c->name }}</h5><button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body row g-2">
                                                    <div class="col-4"><label>Kode</label><input required name="code"
                                                            class="form-control" value="{{ $c->code }}"></div>
                                                    <div class="col-8"><label>Nama</label><input required name="name"
                                                            class="form-control" value="{{ $c->name }}"></div>
                                                    @foreach ([['asset_coa_id', 'COA Asset'], ['accumulated_depreciation_coa_id', 'Akumulasi'], ['depreciation_expense_coa_id', 'Beban Penyusutan'], ['disposal_gain_coa_id', 'Untung Pelepasan'], ['disposal_loss_coa_id', 'Rugi Pelepasan']] as [$n, $l])
                                                        <div class="col-md-6"><label>{{ $l }}</label><select data-app-picker
                                                                data-placeholder="Cari akun..."
                                                                required name="{{ $n }}" class="form-select">
                                                                @foreach ($accounts as $a)
                                                                    <option value="{{ $a->id }}"
                                                                        @selected($c->$n === $a->id)>{{ $a->kode_akun }} —
                                                                        {{ $a->nama_akun }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    @endforeach
                                                    <div class="col-12 form-check ms-2">
                                                        <input type="hidden" name="is_active" value="0"><input
                                                            class="form-check-input" type="checkbox" name="is_active"
                                                            value="1" @checked($c->is_active)><label
                                                            class="form-check-label">Aktif</label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer"><button type="button" class="btn btn-light"
                                                        data-bs-dismiss="modal">Batal</button><button
                                                        class="btn btn-primary">Simpan</button></div>
                                            </form>
                                        </div>
                                    </div>
                                @endcan
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</div>@endsection
@push('scripts')
    <script>
        document.querySelectorAll('.swal-confirm-form').forEach(form => form.addEventListener('submit', async e => {
            if (form.dataset.confirmed) return;
            e.preventDefault();
            const result = await AppAlert.confirm(form.dataset.confirm);
            if (result.isConfirmed) {
                form.dataset.confirmed = '1';
                form.submit()
            }
        }));
    </script>
@endpush
