<dialog id="createModal" class="modal">
    <div class="modal-box max-w-5xl p-0 overflow-hidden" x-data="requestCreateForm({
        documentNumber: '{{ $documentNumber }}',
        bahans: {{ \Illuminate\Support\Js::from($bahans->map(fn($b) => [
                'id' => $b->id,
                'nama' => $b->nama,
                'satuan' => $b->satuan,
                'kategori' => $b->kategori,
                'tipe_gudang' => $b->tipe_gudang,
                'tipe_barang' => $b->tipe_barang,
                'berat_kecil' => $b->berat_kecil,
                'satuan_kecil' => $b->satuan_kecil,
                'keterangan_bahan' => $b->keterangan_bahan,
            ])) }},
    })">
        <div class="flex items-center gap-3 bg-primary px-6 py-4 text-primary-content">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-content/20">
                <i class="fa-solid fa-cart-flatbed"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold">Form Pengajuan Request Barang</h3>
                <p class="text-sm text-primary-content/80">Lengkapi detail pengajuan bahan atau barang kebutuhan perusahaan</p>
            </div>
        </div>

        <form action="{{ route('request.store') }}" method="POST" @submit.prevent="submit" class="flex flex-col">
            @csrf
            <div class="max-h-[70vh] overflow-y-auto p-6">
                <div class="form-control mb-4 max-w-xs">
                    <label class="label"><span class="label-text font-semibold">Nomor Request</span></label>
                    <input type="text" class="input input-bordered bg-base-200" :value="documentNumber" readonly>
                    <span class="label-text-alt mt-1 text-base-content/50">Nomor sudah disiapkan otomatis dan tidak dapat diubah.</span>
                </div>

                <template x-for="(item, idx) in items" :key="idx">
                    <div class="card mb-3 border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body gap-3 p-4">
                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                                <div class="lg:col-span-8">
                                    <label class="label"><span class="label-text font-semibold">Nama Barang / Bahan <span class="text-error">*</span></span></label>
                                    <div class="join w-full">
                                        <input type="text" x-model="item.nama_barang" :readonly="!!item.bahan_id"
                                            class="input input-bordered join-item flex-1" placeholder="Ketik nama barang baru atau cari dari master..." required>
                                        <button type="button" class="join-item btn btn-outline" @click="openPicker(idx)">
                                            <i class="fa-solid fa-magnifying-glass"></i> Master
                                        </button>
                                        <button type="button" x-show="item.bahan_id" class="join-item btn btn-outline" @click="resetItem(idx)">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="lg:col-span-4">
                                    <label class="label"><span class="label-text font-semibold">Jumlah Minta <span class="text-error">*</span></span></label>
                                    <input type="number" step="any" min="0.01" x-model="item.jumlah_minta"
                                        class="input input-bordered w-full" placeholder="0" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-6">
                                <div class="xl:col-span-2">
                                    <label class="label"><span class="label-text font-semibold text-xs">Kategori Bahan <span class="text-error">*</span></span></label>
                                    <select x-model="item.kategori" class="select select-bordered w-full" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach ($kategoris as $k)
                                            <option value="{{ $k->id }}">{{ $k->katnama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="label"><span class="label-text font-semibold text-xs">Satuan Utama <span class="text-error">*</span></span></label>
                                    <input type="text" x-model="item.satuan" class="input input-bordered w-full" placeholder="PCS / KG" required>
                                </div>
                                <div class="xl:col-span-2">
                                    <label class="label"><span class="label-text font-semibold text-xs">Tipe Gudang <span class="text-error">*</span></span></label>
                                    <select x-model="item.tipe_gudang" class="select select-bordered w-full" required>
                                        <option value="">-- Pilih --</option>
                                        @foreach ($gudangs as $g)
                                            <option value="{{ $g->id }}">{{ $g->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="label"><span class="label-text font-semibold text-xs">Isi/Utama</span></label>
                                    <input type="number" step="any" x-model="item.berat_kecil" class="input input-bordered w-full"
                                        title="Jumlah satuan kecil dalam 1 satuan utama">
                                </div>
                                <div>
                                    <label class="label"><span class="label-text font-semibold text-xs">Sat. Kecil</span></label>
                                    <input type="text" x-model="item.satuan_kecil" class="input input-bordered w-full" placeholder="Opsional">
                                </div>
                                <div class="flex items-end gap-2 md:col-span-2 xl:col-span-3">
                                    <div class="flex-1">
                                        <label class="label"><span class="label-text font-semibold text-xs">Keterangan</span></label>
                                        <input type="text" x-model="item.keterangan" class="input input-bordered w-full" placeholder="Spesifikasi">
                                    </div>
                                    <button type="button" class="btn btn-outline btn-error" @click="removeItem(idx)" x-show="items.length > 1">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="pickerOpen" x-cloak class="mb-3 rounded-lg border border-base-300 bg-base-200/40 p-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h6 class="font-bold"><i class="fa-solid fa-boxes-stacked text-primary"></i> Pilih Barang dari Master</h6>
                            <p class="text-sm text-base-content/50">Cari lalu pilih barang untuk baris request yang aktif.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline" @click="pickerOpen = false">
                            <i class="fa-solid fa-xmark"></i> Tutup
                        </button>
                    </div>
                    <label class="input input-bordered mb-3 flex w-full max-w-xs items-center gap-2">
                        <i class="fa-solid fa-magnifying-glass text-base-content/40"></i>
                        <input type="search" class="grow" placeholder="Cari bahan..." x-model="pickerSearch">
                    </label>
                    <div class="max-h-72 overflow-y-auto overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nama Bahan</th>
                                    <th>Satuan</th>
                                    <th class="w-24 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="bahan in filteredBahans" :key="bahan.id">
                                    <tr>
                                        <td class="font-semibold" x-text="bahan.nama"></td>
                                        <td x-text="bahan.satuan || '-'"></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-success btn-xs" @click="pickBahan(bahan)">
                                                <i class="fa-solid fa-check"></i> Pilih
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <button type="button" class="btn btn-outline btn-primary" @click="addItem()">
                    <i class="fa-solid fa-plus"></i> Tambah Item Request
                </button>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="submitting" class="loading loading-spinner loading-sm"></span>
                    <i class="fa-solid fa-paper-plane" x-show="!submitting"></i> Kirim Request
                </button>
            </div>
        </form>
    </div>
</dialog>

<script>
    function requestCreateForm(config) {
        return {
            documentNumber: config.documentNumber,
            bahans: config.bahans,
            pickerOpen: false,
            pickerSearch: '',
            activePickerIndex: null,
            submitting: false,
            items: [{
                bahan_id: '', nama_barang: '', jumlah_minta: '', kategori: '', tipe_barang: '',
                satuan: '', tipe_gudang: '', berat_kecil: '1.0', satuan_kecil: '', keterangan: '',
            }],

            get filteredBahans() {
                const term = this.pickerSearch.trim().toLocaleLowerCase('id-ID');
                if (!term) return this.bahans;
                return this.bahans.filter((b) => b.nama.toLocaleLowerCase('id-ID').includes(term));
            },

            addItem() {
                this.items.push({
                    bahan_id: '', nama_barang: '', jumlah_minta: '', kategori: '', tipe_barang: '',
                    satuan: '', tipe_gudang: '', berat_kecil: '1.0', satuan_kecil: '', keterangan: '',
                });
            },

            removeItem(idx) {
                if (this.items.length > 1) this.items.splice(idx, 1);
            },

            resetItem(idx) {
                this.items[idx] = {
                    bahan_id: '', nama_barang: '', jumlah_minta: this.items[idx].jumlah_minta,
                    kategori: '', tipe_barang: '', satuan: '', tipe_gudang: '',
                    berat_kecil: '1.0', satuan_kecil: '', keterangan: '',
                };
            },

            openPicker(idx) {
                this.activePickerIndex = idx;
                this.pickerOpen = true;
                this.pickerSearch = '';
            },

            pickBahan(bahan) {
                if (this.activePickerIndex === null) return;
                const item = this.items[this.activePickerIndex];
                item.bahan_id = bahan.id;
                item.nama_barang = bahan.nama;
                if (bahan.kategori) { item.kategori = bahan.kategori; item.tipe_barang = bahan.kategori; }
                if (bahan.satuan) item.satuan = bahan.satuan;
                if (bahan.tipe_gudang) item.tipe_gudang = bahan.tipe_gudang;
                if (bahan.berat_kecil) item.berat_kecil = bahan.berat_kecil;
                if (bahan.satuan_kecil) item.satuan_kecil = bahan.satuan_kecil;
                if (bahan.keterangan_bahan) item.keterangan = bahan.keterangan_bahan;
                this.pickerOpen = false;
            },

            async submit(event) {
                this.submitting = true;
                try {
                    const form = event.target;
                    const payload = new FormData(form);
                    payload.set('no_request', this.documentNumber);
                    this.items.forEach((item, idx) => {
                        Object.entries(item).forEach(([key, value]) => {
                            payload.set(`items[${idx}][${key}]`, value ?? '');
                        });
                        payload.set(`items[${idx}][tipe_barang]`, item.kategori ?? '');
                    });

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: payload,
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        window.AppAlert.ajaxError(data);
                        return;
                    }

                    window.AppAlert.auto(data.message ? data : 'Request berhasil disimpan.');
                    window.dispatchEvent(new CustomEvent('wms:table-refresh'));
                    this.$root.closest('dialog')?.close();
                } catch (error) {
                    window.AppAlert.error('Gagal menyimpan request.');
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>
