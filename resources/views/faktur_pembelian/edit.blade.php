<dialog id="editInvoiceModal" class="modal">
    <div class="modal-box max-w-5xl p-0 overflow-hidden" x-data="{
        isPpn: {{ $invoice->ppn > 0 ? 'true' : 'false' }},
        diskon: {{ $invoice->diskon }},
        ongkir: {{ $invoice->ongkir }},
        subTotal: {{ $invoice->sub_total }},
        submitting: false,
        get ppnNominal() { return this.isPpn ? Math.round((this.subTotal * 11) / 100) : 0; },
        get grandTotal() { return (this.subTotal + this.ppnNominal + (Number(this.ongkir) || 0)) - (Number(this.diskon) || 0); },
        async submit(event) {
            const form = event.target;
            if (this.isPpn && !/^\d{3}\.\d{3}-\d{2}\.\d{8}$/.test((form.no_faktur_pajak.value || '').trim())) {
                window.AppAlert.warning('Nomor Faktur Pajak wajib diisi dengan format yang benar saat PPN dipakai.');
                return;
            }
            this.submitting = true;
            try {
                const method = form.querySelector('input[name="_method"]')?.value.toUpperCase() || 'POST';
                const response = await fetch(form.action, {
                    method,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: new URLSearchParams(new FormData(form)),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) { window.AppAlert.ajaxError(data); return; }
                window.AppAlert.auto(data);
                window.dispatchEvent(new CustomEvent('wms:table-refresh'));
                form.closest('dialog').close();
            } catch (error) {
                window.AppAlert.error('Gagal memperbarui invoice.');
            } finally {
                this.submitting = false;
            }
        },
    }">
        <div class="bg-warning px-6 py-4 text-warning-content">
            <h3 class="text-lg font-bold"><i class="fa-solid fa-pen-to-square"></i> Edit Invoice LPB ({{ $invoice->no_invoice }})</h3>
        </div>
        <form action="{{ route('faktur-pembelian.update', $invoice->id) }}" method="POST" @submit.prevent="submit($event)"
            data-autosave data-autosave-key="invoice-lpb-edit-{{ $invoice->id }}" class="flex flex-col">
            @csrf
            @method('PUT')
            <input type="hidden" name="kode_supplier" value="{{ $invoice->kode_supplier }}">

            <div class="max-h-[70vh] overflow-y-auto p-6">
                <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-semibold">LPB dalam Invoice</span></label>
                            <select class="select select-bordered" name="lpb_ids[]" multiple required data-app-picker data-placeholder="Cari dan pilih LPB/BAP supplier...">
                                @foreach ($lpbs as $lpb)
                                    <option value="{{ $lpb->id }}" @selected($invoice->lpbs->contains('id', $lpb->id))>
                                        {{ $lpb->id_lpb }} — {{ $lpb->pembelian->supplier->nama ?? '-' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">No. Invoice Supplier <span class="text-error">*</span></span></label>
                                <input type="text" class="input input-bordered" name="no_invoice" value="{{ $invoice->no_invoice }}" required>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">Supplier</span></label>
                                <input type="text" class="input input-bordered bg-base-200" value="{{ $invoice->supplier->nama ?? '-' }}" readonly>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">Tanggal Invoice <span class="text-error">*</span></span></label>
                                <input type="date" class="input input-bordered" name="tanggal" value="{{ $invoice->tanggal->format('Y-m-d') }}" required>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text font-semibold">Deadline Pembayaran</span></label>
                                <input type="date" class="input input-bordered" name="tgl_deadline_pembayaran" value="{{ optional($invoice->tgl_deadline_pembayaran)->format('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                        <label class="label"><span class="label-text font-semibold">Catatan / Note</span></label>
                        <textarea class="textarea textarea-bordered h-full" name="note" rows="5" placeholder="Catatan opsional...">{{ $invoice->note }}</textarea>
                    </div>
                    <div class="card border border-base-300 bg-base-100 p-4 shadow-sm">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="font-semibold text-base-content/60">Sub Total:</span>
                            <span class="text-lg font-bold">Rp {{ number_format($invoice->sub_total, 0, ',', '.') }}</span>
                        </div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" name="is_ppn" value="1" x-model="isPpn" class="checkbox">
                                <span class="font-semibold">Gunakan PPN (11%)</span>
                            </label>
                            <span class="font-semibold text-base-content/60" x-text="'Rp ' + ppnNominal.toLocaleString('id-ID')"></span>
                        </div>
                        <div x-show="isPpn" x-cloak class="mb-2">
                            <label class="label"><span class="label-text font-semibold">No. Faktur Pajak (NSFP) <span class="text-error">*</span></span></label>
                            <input type="text" class="input input-bordered input-sm w-full" name="no_faktur_pajak" value="{{ $invoice->no_faktur_pajak }}"
                                placeholder="010.001-23.12345678" pattern="\d{3}\.\d{3}-\d{2}\.\d{8}">
                            <span class="label-text-alt mt-1 text-base-content/50">Format: 010.001-23.12345678 — wajib diisi bila PPN dipakai, syarat kredit PPN Masukan.</span>
                        </div>
                        <div class="mb-2 flex items-center gap-3">
                            <label class="w-24 font-semibold">Diskon:</label>
                            <input type="number" step="any" min="0" name="diskon" x-model.number="diskon" data-money-input class="input input-bordered input-sm flex-1 text-end">
                        </div>
                        <div class="mb-2 flex items-center gap-3">
                            <label class="w-24 font-semibold">Ongkir:</label>
                            <input type="number" step="any" min="0" name="ongkir" x-model.number="ongkir" data-money-input class="input input-bordered input-sm flex-1 text-end">
                        </div>
                        <div role="alert" class="alert alert-info mb-2 text-sm">PPh 23 dicatat saat pembayaran.</div>
                        <div class="divider my-1"></div>
                        <div class="flex items-center justify-between">
                            <h5 class="text-lg font-bold">Grand Total:</h5>
                            <h5 class="text-lg font-bold text-primary" x-text="'Rp ' + grandTotal.toLocaleString('id-ID')"></h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-warning" :disabled="submitting">
                    <span x-show="submitting" class="loading loading-spinner loading-sm"></span>
                    <i class="fa-solid fa-rotate" x-show="!submitting"></i> Perbarui Invoice
                </button>
            </div>
        </form>
    </div>
</dialog>
