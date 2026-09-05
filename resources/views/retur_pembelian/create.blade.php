<dialog id="createReturModal" class="modal">
    <div class="modal-box max-w-4xl p-0 overflow-hidden" x-data="returPembelianForm({ documentNumber: '{{ $documentNumber }}' })">
        <div class="bg-primary px-6 py-4 text-primary-content">
            <h3 class="text-lg font-bold"><i class="fa-solid fa-rotate-left"></i> Buat Retur Pembelian Baru</h3>
        </div>
        <form @submit.prevent="submit()" class="flex flex-col">
            <div class="max-h-[70vh] overflow-y-auto p-6">
                <div class="rounded-lg border border-base-300 p-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Nomor Retur</span></label>
                            <input type="text" class="input input-bordered bg-base-200" :value="documentNumber" readonly>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Pilih Penerimaan Barang <span class="text-error">*</span></span></label>
                            <select class="select select-bordered" x-model="idLpb" @change="loadLpbDetail()" required
                                data-app-picker data-placeholder="Cari nomor penerimaan barang, PO, atau supplier...">
                                <option value=""></option>
                                @foreach ($lpbs as $lpb)
                                    <option value="{{ $lpb->id_lpb }}" data-id="{{ $lpb->id }}">
                                        {{ $lpb->id_lpb }} — {{ $lpb->pembelian->supplier->nama ?? '-' }} ({{ $lpb->tanggal?->format('d-m-Y') }}){{ $lpb->no_invoice ? ' — sudah ditagih' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Tanggal Retur <span class="text-error">*</span></span></label>
                            <input type="date" x-model="tanggal" class="input input-bordered" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Alasan Retur <span class="text-error">*</span></span></label>
                            <input type="text" x-model="alasan" class="input input-bordered" placeholder="Contoh: barang rusak, tidak sesuai spesifikasi" required maxlength="1000">
                        </div>
                    </div>
                    <div class="alert alert-info mt-4 text-sm" x-show="lpbInfo.no_invoice" x-cloak>
                        <i class="fa-solid fa-circle-info"></i>
                        <span x-show="lpbInfo.invoice_sisa_tagihan > 0">
                            Penerimaan barang ini sudah ditagih pada invoice <span x-text="lpbInfo.no_invoice" class="font-bold"></span>.
                            Nilai retur akan mengurangi sisa hutang invoice tersebut; kelebihannya (jika ada) menjadi uang muka supplier.
                        </span>
                        <span x-show="!(lpbInfo.invoice_sisa_tagihan > 0)">
                            Invoice <span x-text="lpbInfo.no_invoice" class="font-bold"></span> untuk penerimaan barang ini sudah lunas.
                            Seluruh nilai retur akan dicatat sebagai uang muka supplier untuk pembayaran berikutnya.
                        </span>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-base-300 p-4">
                    <h6 class="mb-3 font-bold"><i class="fa-solid fa-boxes-stacked text-primary"></i> Barang yang Diretur</h6>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Nama Bahan</th>
                                    <th class="text-center">Tersedia untuk Retur</th>
                                    <th class="text-end">Harga Satuan</th>
                                    <th class="text-center">Jumlah Retur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="items.length === 0">
                                    <tr>
                                        <td colspan="5" class="py-3 text-center text-base-content/50" x-text="idLpb ? 'Tidak ada barang yang masih tersedia untuk diretur pada penerimaan barang ini.' : 'Pilih penerimaan barang terlebih dahulu.'"></td>
                                    </tr>
                                </template>
                                <template x-for="(item, idx) in items" :key="item.id">
                                    <tr>
                                        <td class="text-center" x-text="idx + 1"></td>
                                        <td class="font-bold" x-text="item.nama_bahan"></td>
                                        <td class="text-center font-bold text-success" x-text="item.jumlah_tersedia_retur"></td>
                                        <td class="text-end" x-text="formatRupiah(item.harga || 0)"></td>
                                        <td class="text-center">
                                            <input type="number" step="any" min="0" :max="item.jumlah_tersedia_retur" x-model.number="item.jumlah_retur" class="input input-bordered input-sm text-end">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="submitting" class="loading loading-spinner loading-sm"></span>
                    <i class="fa-solid fa-floppy-disk" x-show="!submitting"></i> Simpan Retur
                </button>
            </div>
        </form>
    </div>
</dialog>

<script>
    function returPembelianForm(config) {
        return {
            documentNumber: config.documentNumber,
            idLpb: '',
            lpbId: '',
            tanggal: new Date().toISOString().substring(0, 10),
            alasan: '',
            items: [],
            lpbInfo: {},
            submitting: false,

            async loadLpbDetail() {
                this.items = [];
                this.lpbInfo = {};
                if (!this.idLpb) { this.lpbId = ''; return; }
                const option = this.$root.querySelector(`option[value="${CSS.escape(this.idLpb)}"]`);
                this.lpbId = option?.dataset.id || '';
                try {
                    const url = '{{ route('retur-pembelian.get-lpb-detail', ':id_lpb') }}'.replace(':id_lpb', this.idLpb);
                    const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                    const res = await response.json();
                    if (!res.success || !res.items.length) { this.items = []; return; }
                    this.items = res.items.map((item) => ({ ...item, jumlah_retur: 0 }));
                    this.lpbInfo = res.lpb || {};
                } catch (error) {
                    window.AppAlert.error('Gagal memuat detail penerimaan barang.');
                }
            },

            async submit() {
                if (!this.lpbId) { window.AppAlert.warning('Pilih penerimaan barang terlebih dahulu.'); return; }
                if (!this.items.some((item) => Number(item.jumlah_retur) > 0)) {
                    window.AppAlert.warning('Isi jumlah retur untuk minimal satu barang.');
                    return;
                }

                this.submitting = true;
                try {
                    const payload = {
                        no_retur: this.documentNumber, lpb_id: this.lpbId, tanggal: this.tanggal, alasan: this.alasan,
                        details: this.items.filter((item) => Number(item.jumlah_retur) > 0).map((item) => ({
                            lpb_detail_id: item.id, jumlah_retur: item.jumlah_retur,
                        })),
                    };
                    const response = await fetch('{{ route('retur-pembelian.store') }}', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) { window.AppAlert.ajaxError(data); return; }
                    window.AppAlert.auto(data);
                    window.dispatchEvent(new CustomEvent('wms:table-refresh'));
                    this.$root.closest('dialog').close();
                } catch (error) {
                    window.AppAlert.error('Gagal menyimpan retur pembelian.');
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>
