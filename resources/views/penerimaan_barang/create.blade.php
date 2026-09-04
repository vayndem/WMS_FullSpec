<dialog id="createPenerimaanBarangModal" class="modal">
    <div class="modal-box max-w-6xl p-0 overflow-hidden" x-data="penerimaanBarangCreateForm({ kategoris: {{ Js::from($kategoris) }} })">
        <div class="flex items-center gap-3 bg-primary px-6 py-4 text-primary-content">
            <i class="fa-solid fa-boxes-packing"></i>
            <h3 class="text-lg font-bold">Buat Penerimaan Barang (LPB)</h3>
        </div>
        <form action="{{ route('penerimaan-barang.store') }}" method="POST" @submit.prevent="submit()" data-autosave data-autosave-key="penerimaan-barang-create" class="flex flex-col">
            @csrf
            <div class="max-h-[70vh] overflow-y-auto p-6">
                <div class="rounded-lg border border-base-300 p-4">
                    <h6 class="mb-3 font-bold"><i class="fa-solid fa-truck-ramp-box"></i> Dokumen Penerimaan</h6>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Nomor Penerimaan Barang</span></label>
                            <input type="text" class="input input-bordered bg-base-200" name="id_lpb" value="{{ $documentNumber }}" readonly>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Nomor PO <span class="text-error">*</span></span></label>
                            <select class="select select-bordered" name="no_po" x-model="noPo" @change="loadPo()" required
                                data-app-picker data-placeholder="Cari nomor PO atau supplier...">
                                <option value="">-- Pilih / Cari PO --</option>
                                @foreach ($pos as $po)
                                    <option value="{{ $po->no_po }}"
                                        data-subtitle="{{ $po->supplier->nama ?? 'Supplier tidak tersedia' }}"
                                        data-meta="{{ $po->tanggal ?? '-' }} · {{ $po->details->count() }} item"
                                        data-badge="PO Barang">{{ $po->no_po }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Tanggal Terima <span class="text-error">*</span></span></label>
                            <input type="date" class="input input-bordered" name="tanggal" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">No. Surat Jalan <span class="text-error">*</span></span></label>
                            <input type="text" class="input input-bordered" name="no_sj" placeholder="Masukkan No. SJ..." required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">No. Invoice</span></label>
                            <input type="text" class="input input-bordered" name="no_invoice" placeholder="Opsi Tambahan...">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Jenis Penerimaan</span></label>
                            <select class="select select-bordered" name="jenis_lpb">
                                <option value="1">Reguler</option>
                                <option value="2">Pengganti / Retur</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Supplier</span></label>
                            <input type="text" class="input input-bordered bg-base-200" :value="supplierNama" readonly placeholder="Terisi dari PO">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">Gudang Tujuan PO</span></label>
                            <input type="text" class="input input-bordered bg-base-200" :value="gudangNama" readonly placeholder="Terisi dari PO">
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-lg border border-base-300 p-4">
                    <h6 class="mb-3 font-bold"><i class="fa-solid fa-list-check text-primary"></i> Rincian Item Diterima</h6>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Nama Bahan</th>
                                    <th>Kategori Barang <span class="text-error">*</span></th>
                                    <th class="text-center">Qty PO</th>
                                    <th class="text-center">Diterima</th>
                                    <th class="text-center">Sisa</th>
                                    <th class="text-center">Terima Fisik <span class="text-error">*</span></th>
                                    <th>Lot Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="items.length === 0">
                                    <tr>
                                        <td colspan="8" class="py-3 text-center text-base-content/50">Pilih Nomor PO terlebih dahulu.</td>
                                    </tr>
                                </template>
                                <template x-for="(item, idx) in items" :key="idx">
                                    <tr>
                                        <td class="text-center" x-text="idx + 1"></td>
                                        <td>
                                            <input type="hidden" :name="`details[${idx}][id_bahan]`" :value="item.bahan_id">
                                            <strong x-text="item.nama_bahan"></strong>
                                        </td>
                                        <td>
                                            <select class="select select-bordered select-sm" :name="`details[${idx}][id_kategori]`" x-model="item.id_kategori" required
                                                data-app-picker data-placeholder="Cari kategori...">
                                                <option value="">-- Pilih Kategori --</option>
                                                <template x-for="kat in kategoris" :key="kat.id">
                                                    <option :value="kat.id" x-text="kat.katnama"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="text-center font-bold" x-text="item.jumlah_po"></td>
                                        <td class="text-center font-bold text-primary" x-text="item.diterima"></td>
                                        <td class="text-center font-bold text-error" x-text="item.sisa"></td>
                                        <td>
                                            <input type="number" step="any" min="0.01" :name="`details[${idx}][jumlah_barang_diterima]`"
                                                x-model="item.jumlah_barang_diterima" class="input input-bordered input-sm text-center font-bold text-success" required>
                                        </td>
                                        <td>
                                            <input type="text" :name="`details[${idx}][lot_number]`" x-model="item.lot_number" class="input input-bordered input-sm" placeholder="No. Lot">
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
                    <i class="fa-solid fa-floppy-disk" x-show="!submitting"></i> Simpan Penerimaan Barang
                </button>
            </div>
        </form>
    </div>
</dialog>

<script>
    function penerimaanBarangCreateForm(config) {
        return {
            kategoris: config.kategoris,
            noPo: '',
            supplierNama: '',
            gudangNama: '',
            items: [],
            submitting: false,
            confirmOverReceive: false,

            async loadPo() {
                if (!this.noPo) {
                    this.items = [];
                    this.supplierNama = '';
                    this.gudangNama = '';
                    return;
                }
                try {
                    const response = await fetch(`{{ url('penerimaan-barang/po') }}/${this.noPo}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    const res = await response.json();
                    if (!res.success) return;
                    this.supplierNama = res.po.supplier ? res.po.supplier.nama : '-';
                    this.gudangNama = res.po.gudang ? res.po.gudang.nama : '-';
                    this.items = res.items.map((item) => ({
                        bahan_id: item.bahan_id, nama_bahan: item.nama_bahan, id_kategori: item.id_kategori || '',
                        jumlah_po: item.jumlah_po, diterima: item.diterima, sisa: item.sisa,
                        jumlah_barang_diterima: item.sisa, lot_number: '',
                    }));
                } catch (error) {
                    window.AppAlert.error('Gagal memuat data PO.');
                }
            },

            async submit() {
                this.submitting = true;
                try {
                    const form = this.$root.querySelector('form');
                    const payload = new FormData(form);
                    payload.set('confirm_over_receive', this.confirmOverReceive ? '1' : '0');

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: payload,
                    });
                    const res = await response.json().catch(() => ({}));

                    if (response.status === 422 && res.requires_confirmation) {
                        let msg = `${res.message}\n\nKonfirmasi over-receive item:\n`;
                        (res.over_items || []).forEach((item) => {
                            msg += `- ${item.nama}: Input ${item.input} (Sisa PO: ${item.minta_sisa})\n`;
                        });
                        const result = await window.AppAlert.confirm(msg, { title: 'Penerimaan melebihi PO' });
                        if (result.isConfirmed) {
                            this.confirmOverReceive = true;
                            await this.submit();
                        }
                        return;
                    }

                    if (!response.ok) { window.AppAlert.ajaxError(res); return; }

                    window.AppAlert.auto(res);
                    form.dispatchEvent(new Event('wms:saved'));
                    window.dispatchEvent(new CustomEvent('wms:table-refresh'));
                    this.$root.closest('dialog').close();
                } catch (error) {
                    window.AppAlert.error('Gagal menyimpan penerimaan barang.');
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>
