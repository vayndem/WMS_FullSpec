<dialog id="modal-create-npk" class="modal">
    <div class="modal-box max-w-4xl p-0 overflow-hidden">
        <div class="bg-primary px-6 py-4 text-primary-content">
            <h3 class="text-lg font-bold"><i class="fa-solid fa-boxes-packing"></i> Buat Pengeluaran Barang (NPK) Baru
            </h3>
        </div>
        <form action="{{ route('pemakaian-barang.store') }}" method="POST" @submit.prevent="submitAjaxForm($event)"
            class="flex flex-col">
            @csrf
            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Kode NPK</span></label>
                    <input type="text" name="kode" class="input input-bordered" value="{{ $documentNumber }}"
                        readonly>
                    <span class="label-text-alt mt-1 text-base-content/50">Format: NPK + tanggal + nomor urut.</span>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Kode Pesanan</span></label>
                    <input type="text" name="kode_datapesanan" class="input input-bordered"
                        placeholder="Masukkan kode pesanan (opsional)">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Perintah Kerja Produksi</span></label>
                    <select name="data_pesanan_id" data-app-picker class="select select-bordered">
                        <option value="">Bukan untuk produksi (langsung jadi beban)</option>
                        @foreach ($perintahKerja as $wo)
                            <option value="{{ $wo->id }}" data-gudang="{{ $wo->gudang_id }}" @selected(old('data_pesanan_id', null) == $wo->id)>
                                {{ $wo->nomor }} &middot; {{ $wo->bahanHasil?->nama }} &middot; {{ $wo->pesananPenjualan?->pelanggan?->nama }}
                            </option>
                        @endforeach
                    </select>
                    <span class="label-text-alt mt-1 text-base-content/50">Kalau diisi, biaya pemakaian ini masuk ke Barang Dalam Proses, bukan langsung ke beban. Gudang harus sama dengan gudang perintah kerja.</span>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Tanggal Transaksi <span
                                class="text-error">*</span></span></label>
                    <input type="date" name="tanggal" class="input input-bordered" value="{{ date('Y-m-d') }}"
                        required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Pilih Barang <span
                                class="text-error">*</span></span></label>
                    <select name="id_barang" id="npk_bahan" class="select select-bordered" required data-app-picker
                        data-placeholder="Cari nama barang, kategori, atau satuan...">
                        <option value="">-- Pilih Barang --</option>
                        @foreach ($bahans as $bahan)
                            <option value="{{ $bahan->id }}" data-unit="{{ $bahan->satuan }}"
                                data-small-unit="{{ $bahan->hasSmallUnit() ? $bahan->satuan_kecil : '' }}"
                                data-factor="{{ $bahan->hasSmallUnit() ? $bahan->berat_kecil : 1 }}"
                                data-stocks='@json($bahan->stokGudangs->pluck('stok_tersedia', 'gudang_id'))'
                                data-subtitle="{{ $bahan->kategoriBahan->katnama ?? 'Kategori belum ditentukan' }}"
                                data-meta="Stok {{ number_format((float) $bahan->stok_onhand, 6, ',', '.') }} {{ $bahan->satuan }}{{ $bahan->hasSmallUnit() ? ' · 1 ' . $bahan->satuan . ' = ' . number_format((float) $bahan->berat_kecil, 6, ',', '.') . ' ' . $bahan->satuan_kecil : '' }}"
                                data-search="{{ $bahan->satuan }} {{ $bahan->satuan_kecil }}">
                                {{ $bahan->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Gudang Asal</span></label>
                    <select name="id_gudang_asal" id="npk_gudang" class="select select-bordered" data-app-picker
                        data-placeholder="Cari gudang asal...">
                        <option value="">-- Pilih Gudang Asal --</option>
                        @foreach ($gudangs as $gudang)
                            <option value="{{ $gudang->id }}" data-subtitle="Lokasi sumber stok">{{ $gudang->nama }}
                            </option>
                        @endforeach
                    </select>
                    <span id="npk_stock_help" class="label-text-alt mt-1 text-base-content/50">Pilih gudang dan barang
                        untuk melihat saldo.</span>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Gudang Tujuan</span></label>
                    <select name="id_gudang_tujuan" class="select select-bordered" data-app-picker
                        data-placeholder="Cari gudang tujuan...">
                        <option value="">-- Pilih Gudang Tujuan --</option>
                        @foreach ($gudangs as $gudang)
                            <option value="{{ $gudang->id }}" data-subtitle="Lokasi tujuan pemakaian">
                                {{ $gudang->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Reservasi / Picking
                            (Opsional)</span></label>
                    <select name="inventory_reservation_id" class="select select-bordered" data-app-picker
                        data-placeholder="Pilih reservasi stok...">
                        <option value="">Tanpa reservasi</option>
                        @foreach ($reservations as $reservation)
                            <option value="{{ $reservation->id }}">{{ $reservation->number }} ·
                                {{ $reservation->gudang->nama }} · {{ $reservation->bahan->nama }} ·
                                {{ $reservation->quantity }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Jumlah Barang <span
                                class="text-error">*</span></span></label>
                    <input type="number" step="any" name="jumlah" class="input input-bordered" placeholder="0"
                        required>
                    <span id="npk_unit_help" class="label-text-alt mt-1 text-base-content/50">Pilih barang untuk melihat
                        satuan NPK.</span>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Status Transaksi <span
                                class="text-error">*</span></span></label>
                    <select name="status" class="select select-bordered" required>
                        <option value="DRAFT">Draft (Belum Potong Stok & COA)</option>
                        <option value="POSTED">Keluar (Potong Stok & Catat COA)</option>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Operator / Penanggung
                            Jawab</span></label>
                    <input type="text" name="operator" class="input input-bordered" placeholder="Nama operator">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Keterangan</span></label>
                    <textarea name="keterangan" class="textarea textarea-bordered" rows="1" placeholder="Catatan tambahan"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</dialog>

<script>
    (function() {
        function refreshNpkUnit() {
            const option = document.querySelector('#npk_bahan')?.selectedOptions[0];
            const help = document.querySelector('#npk_unit_help');
            if (!option || !help) return;
            const small = option.dataset.smallUnit;
            const base = option.dataset.unit || '';
            const factor = Number(option.dataset.factor || 1);
            help.textContent = small ?
                `Input dalam ${small}. Konversi: 1 ${base} = ${factor.toLocaleString('id-ID')} ${small}.` :
                `Input dalam satuan utama ${base}.`;
        }

        function refreshNpkStock() {
            const option = document.querySelector('#npk_bahan')?.selectedOptions[0];
            const warehouse = document.querySelector('#npk_gudang')?.value;
            const help = document.querySelector('#npk_stock_help');
            if (!option || !warehouse || !help) return;
            const stocks = JSON.parse(option.dataset.stocks || '{}');
            help.textContent =
                `Saldo gudang: ${Number(stocks[warehouse] || 0).toLocaleString('id-ID')} ${option.dataset.unit || ''}`;
        }
        document.querySelector('#npk_bahan')?.addEventListener('change', () => {
            refreshNpkUnit();
            refreshNpkStock();
        });
        document.querySelector('#npk_gudang')?.addEventListener('change', refreshNpkStock);
        refreshNpkUnit();
    })();
</script>
