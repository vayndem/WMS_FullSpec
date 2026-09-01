@php($editing = isset($opname) && $opname->exists)
@php($selected = $editing ? $opname->details->keyBy('bahan_id') : collect())
<div x-data="stockOpnameForm({
    warehouseId: '{{ $editing ? $opname->warehouse_id : '' }}',
    items: {{ Js::from(
        $stocks->map(
            fn($stock) => [
                'bahan_id' => $stock->bahan->id,
                'nama' => $stock->bahan->nama,
                'tipe' => $stock->bahan->tipeBarang->katnama ?? '-',
                'warehouse_id' => $stock->gudang_id,
                'stok_tersedia' => number_format($stock->stok_tersedia, 6, ',', '.'),
                'satuan' => $stock->bahan->satuan,
                'physical_quantity' =>
                    optional($selected->get($stock->bahan->id))->physical_quantity ?? $stock->stok_tersedia,
                'reason' => optional($selected->get($stock->bahan->id))->reason ?? '',
                'notes' => optional($selected->get($stock->bahan->id))->notes ?? '',
                'checked' => $selected->has($stock->bahan->id),
            ],
        ),
    ) }},
})">
    <form action="{{ $editing ? route('stock-opname.update', $opname) : route('stock-opname.store') }}" method="POST"
        @submit.prevent="submit($event)" data-autosave
        data-autosave-key="stock-opname-{{ $editing ? 'edit-' . $opname->id : 'create' }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif
        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
            <div class="form-control">
                <label class="label"><span class="label-text font-semibold">Nomor Opname</span></label>
                <input class="input input-bordered bg-base-200" name="number"
                    value="{{ $editing ? $opname->number : $documentNumber }}" readonly>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text font-semibold">Gudang</span></label>
                <select class="select select-bordered" name="warehouse_id" x-model="warehouseId" required
                    data-app-picker data-placeholder="Cari gudang opname...">
                    <option value="">Pilih gudang</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text font-semibold">Waktu Cut-off</span></label>
                <input class="input input-bordered" type="datetime-local" name="cutoff_at"
                    value="{{ $editing ? $opname->cutoff_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i') }}"
                    required>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text font-semibold">Catatan</span></label>
                <input class="input input-bordered" name="notes" value="{{ $editing ? $opname->notes : '' }}">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pilih</th>
                        <th>Barang</th>
                        <th>Stok Sistem</th>
                        <th>Fisik</th>
                        <th>Alasan jika Selisih</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, idx) in visibleItems" :key="item.bahan_id">
                        <tr>
                            <td><input type="checkbox" class="checkbox" x-model="item.checked"></td>
                            <td>
                                <strong x-text="item.nama"></strong>
                                <small class="block text-base-content/50" x-text="item.tipe"></small>
                            </td>
                            <td x-text="`${item.stok_tersedia} ${item.satuan}`"></td>
                            <td><input type="number" min="0" step="0.000001"
                                    class="input input-bordered input-sm" x-model.number="item.physical_quantity"
                                    :disabled="!item.checked" required></td>
                            <td><input type="text" class="input input-bordered input-sm" x-model="item.reason"
                                    :disabled="!item.checked" placeholder="Rusak/hilang/koreksi"></td>
                            <td><input type="text" class="input input-bordered input-sm" x-model="item.notes"
                                    :disabled="!item.checked"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <a href="{{ route('stock-opname.index') }}" class="btn btn-ghost border border-base-300">Batal</a>
            <button type="submit" class="btn btn-primary" :disabled="submitting">
                <span x-show="submitting" class="loading loading-spinner loading-sm"></span>
                Simpan Draft
            </button>
        </div>
    </form>
</div>

<script>
    function stockOpnameForm(config) {
        return {
            warehouseId: config.warehouseId,
            items: config.items,
            submitting: false,

            get visibleItems() {
                return this.items.filter((item) => String(item.warehouse_id) === String(this.warehouseId));
            },

            async submit(event) {
                const checkedItems = this.visibleItems.filter((item) => item.checked);
                if (checkedItems.length === 0) {
                    window.AppAlert.warning('Pilih minimal satu barang.');
                    return;
                }

                this.submitting = true;
                try {
                    const form = event.target;
                    const payload = new FormData(form);
                    checkedItems.forEach((item, idx) => {
                        payload.set(`items[${idx}][bahan_id]`, item.bahan_id);
                        payload.set(`items[${idx}][physical_quantity]`, item.physical_quantity);
                        payload.set(`items[${idx}][reason]`, item.reason || '');
                        payload.set(`items[${idx}][notes]`, item.notes || '');
                    });

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: payload,
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        window.AppAlert.ajaxError(data);
                        return;
                    }
                    form.dispatchEvent(new Event('wms:saved'));
                    await window.AppAlert.success(data.message);
                    window.location.href = '{{ route('stock-opname.index') }}';
                } catch (error) {
                    window.AppAlert.error('Gagal menyimpan stock opname.');
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>
