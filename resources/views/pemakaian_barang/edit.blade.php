<dialog id="modal-edit-npk" class="modal">
    <div class="modal-box max-w-4xl p-0 overflow-hidden">
        <div class="bg-info px-6 py-4 text-info-content">
            <h3 class="text-lg font-bold"><i class="fa-solid fa-pen-to-square"></i> Edit Pengeluaran Barang
                ({{ $npk->kode }})</h3>
        </div>
        <form action="{{ route('pemakaian-barang.update', $npk->id) }}" method="POST" @submit.prevent="submitAjaxForm($event)"
            class="flex flex-col">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Kode NPK</span></label>
                    <input type="text" name="kode" class="input input-bordered" value="{{ $npk->kode }}"
                        readonly>
                    <span class="label-text-alt mt-1 text-base-content/50">Kode tidak dapat diubah.</span>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Kode Pesanan</span></label>
                    <input type="text" name="kode_datapesanan" class="input input-bordered"
                        value="{{ $npk->kode_datapesanan }}">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Tanggal Transaksi <span
                                class="text-error">*</span></span></label>
                    <input type="date" name="tanggal" class="input input-bordered" value="{{ $npk->tanggal->format('Y-m-d') }}"
                        required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Pilih Barang <span
                                class="text-error">*</span></span></label>
                    <select name="id_barang" id="npk_edit_bahan" class="select select-bordered" required data-app-picker
                        data-placeholder="Cari barang...">
                        <option value="">-- Pilih Barang --</option>
                        @foreach ($bahans as $bahan)
                            <option value="{{ $bahan->id }}" data-unit="{{ $bahan->satuan }}"
                                data-small-unit="{{ $bahan->hasSmallUnit() ? $bahan->satuan_kecil : '' }}"
                                data-factor="{{ $bahan->hasSmallUnit() ? $bahan->berat_kecil : 1 }}"
                                @selected($npk->id_barang == $bahan->id)>
                                {{ $bahan->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Gudang Asal</span></label>
                    <select name="id_gudang_asal" class="select select-bordered" data-app-picker
                        data-placeholder="Cari gudang asal...">
                        <option value="">-- Pilih Gudang Asal --</option>
                        @foreach ($gudangs as $gudang)
                            <option value="{{ $gudang->id }}"
                                {{ $npk->id_gudang_asal == $gudang->id ? 'selected' : '' }}>{{ $gudang->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Gudang Tujuan</span></label>
                    <select name="id_gudang_tujuan" class="select select-bordered" data-app-picker
                        data-placeholder="Cari gudang tujuan...">
                        <option value="">-- Pilih Gudang Tujuan --</option>
                        @foreach ($gudangs as $gudang)
                            <option value="{{ $gudang->id }}"
                                {{ $npk->id_gudang_tujuan == $gudang->id ? 'selected' : '' }}>{{ $gudang->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Reservasi / Picking
                            (Opsional)</span></label>
                    <select name="inventory_reservation_id" class="select select-bordered" data-app-picker>
                        <option value="">Tanpa reservasi</option>
                        @foreach ($reservations as $reservation)
                            <option value="{{ $reservation->id }}"
                                {{ $npk->inventory_reservation_id == $reservation->id ? 'selected' : '' }}>
                                {{ $reservation->number }} · {{ $reservation->gudang->nama }} ·
                                {{ $reservation->bahan->nama }} · {{ $reservation->quantity }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Jumlah Barang <span
                                class="text-error">*</span></span></label>
                    <input type="number" step="any" name="jumlah" class="input input-bordered"
                        value="{{ $npk->jumlah }}" required>
                    <span id="npk_edit_unit_help" class="label-text-alt mt-1 text-base-content/50"></span>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Status Transaksi <span
                                class="text-error">*</span></span></label>
                    <select name="status" class="select select-bordered" required>
                        <option value="DRAFT" {{ $npk->status === \App\Models\PemakaianBarang::DRAFT ? 'selected' : '' }}>Draft
                            (Belum Potong Stok & COA)</option>
                        <option value="POSTED" {{ $npk->status === \App\Models\PemakaianBarang::POSTED ? 'selected' : '' }}>Keluar
                            (Potong Stok & Catat COA)</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Operator / Penanggung
                            Jawab</span></label>
                    <input type="text" name="operator" class="input input-bordered" value="{{ $npk->operator }}">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Keterangan</span></label>
                    <textarea name="keterangan" class="textarea textarea-bordered" rows="1">{{ $npk->keterangan }}</textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-info"><i class="fa-solid fa-rotate"></i> Perbarui</button>
            </div>
        </form>
    </div>
</dialog>

<script>
    (function() {
        function refreshEditNpkUnit() {
            const option = document.querySelector('#npk_edit_bahan')?.selectedOptions[0];
            const help = document.querySelector('#npk_edit_unit_help');
            if (!option || !help) return;
            const small = option.dataset.smallUnit;
            const base = option.dataset.unit || '';
            const factor = Number(option.dataset.factor || 1);
            help.textContent = small ?
                `Input dalam ${small}. Konversi: 1 ${base} = ${factor.toLocaleString('id-ID')} ${small}.` :
                `Input dalam satuan utama ${base}.`;
        }
        document.querySelector('#npk_edit_bahan')?.addEventListener('change', refreshEditNpkUnit);
        refreshEditNpkUnit();
    })();
</script>
