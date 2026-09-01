@extends('layouts.app')
@section('content')
    <div class="content-page">
        <div class="mb-4">
            <h3 class="text-2xl font-bold">Kategori &amp; Mapping Asset</h3>
            <p class="text-base-content/60">Mapping jurnal perolehan, penyusutan, dan pelepasan asset.</p>
        </div>
        @can('create', App\Models\AssetCategory::class)
            <form method="post" action="{{ route('asset-categories.store') }}" class="card mb-4 border border-base-300 bg-base-100 p-4 shadow-sm">
                @csrf
                <div class="grid grid-cols-1 gap-2 md:grid-cols-5">
                    <input required name="code" class="input input-bordered" placeholder="Kode">
                    <input required name="name" class="input input-bordered md:col-span-2" placeholder="Nama kategori">
                    @foreach ([['asset_coa_id', 'COA Asset'], ['accumulated_depreciation_coa_id', 'Akumulasi'], ['depreciation_expense_coa_id', 'Beban Penyusutan'], ['disposal_gain_coa_id', 'Untung Pelepasan'], ['disposal_loss_coa_id', 'Rugi Pelepasan']] as [$n, $l])
                        <select required name="{{ $n }}" class="select select-bordered" data-app-picker data-placeholder="Cari akun...">
                            <option value="">{{ $l }}</option>
                            @foreach ($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->kode_akun }} — {{ $a->nama_akun }}</option>
                            @endforeach
                        </select>
                    @endforeach
                    <input type="hidden" name="is_active" value="1">
                </div>
                <div class="mt-3 text-end">
                    <button class="btn btn-primary">Tambah Kategori</button>
                </div>
            </form>
        @endcan
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="overflow-x-auto">
                <table class="table">
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
                                <td class="font-semibold">{{ $c->name }}</td>
                                <td>{{ $c->assetAccount->kode_akun }} — {{ $c->assetAccount->nama_akun }}</td>
                                <td>{{ $c->accumulatedAccount->kode_akun }}</td>
                                <td>{{ $c->expenseAccount->kode_akun }}</td>
                                <td><span class="badge {{ $c->is_active ? 'badge-success' : 'badge-ghost' }}">{{ $c->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="text-end">
                                    @can('update', $c)
                                        <button class="btn btn-outline btn-primary btn-sm" onclick="editCategory{{ $c->id }}.showModal()">Edit</button>
                                    @endcan
                                    @can('delete', $c)
                                        <form method="post" action="{{ route('asset-categories.destroy', $c) }}" class="inline swal-confirm-form" data-confirm="Hapus kategori asset ini?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline btn-error btn-sm">Hapus</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                            @can('update', $c)
                                <dialog id="editCategory{{ $c->id }}" class="modal">
                                    <div class="modal-box max-w-2xl">
                                        <form method="post" action="{{ route('asset-categories.update', $c) }}">
                                            @csrf
                                            @method('PUT')
                                            <h3 class="mb-4 text-lg font-bold">Edit {{ $c->name }}</h3>
                                            <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                                                <input required name="code" class="input input-bordered" value="{{ $c->code }}" placeholder="Kode">
                                                <input required name="name" class="input input-bordered md:col-span-2" value="{{ $c->name }}" placeholder="Nama">
                                                @foreach ([['asset_coa_id', 'COA Asset'], ['accumulated_depreciation_coa_id', 'Akumulasi'], ['depreciation_expense_coa_id', 'Beban Penyusutan'], ['disposal_gain_coa_id', 'Untung Pelepasan'], ['disposal_loss_coa_id', 'Rugi Pelepasan']] as [$n, $l])
                                                    <select data-app-picker data-placeholder="Cari akun..." required name="{{ $n }}" class="select select-bordered">
                                                        <option value="">{{ $l }}</option>
                                                        @foreach ($accounts as $a)
                                                            <option value="{{ $a->id }}" @selected($c->$n === $a->id)>{{ $a->kode_akun }} — {{ $a->nama_akun }}</option>
                                                        @endforeach
                                                    </select>
                                                @endforeach
                                                <label class="flex cursor-pointer items-center gap-2">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input class="checkbox" type="checkbox" name="is_active" value="1" @checked($c->is_active)>
                                                    <span>Aktif</span>
                                                </label>
                                            </div>
                                            <div class="mt-4 flex justify-end gap-2">
                                                <button type="button" class="btn btn-ghost" onclick="editCategory{{ $c->id }}.close()">Batal</button>
                                                <button class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-backdrop" onclick="editCategory{{ $c->id }}.close()"></div>
                                </dialog>
                            @endcan
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.querySelectorAll('.swal-confirm-form').forEach(form => form.addEventListener('submit', async e => {
            if (form.dataset.confirmed) return;
            e.preventDefault();
            const result = await AppAlert.confirm(form.dataset.confirm);
            if (result.isConfirmed) {
                form.dataset.confirmed = '1';
                form.submit();
            }
        }));
    </script>
@endpush
