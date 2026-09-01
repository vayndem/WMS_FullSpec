<dialog id="editJurnalModal" class="modal">
    <div class="modal-box max-w-3xl p-0 overflow-hidden">
        <div class="bg-warning px-6 py-4 text-warning-content">
            <h3 class="text-lg font-bold"><i class="fa-solid fa-pen-to-square"></i> Edit Header Jurnal</h3>
        </div>
        <form action="{{ route('jurnal.update', $jurnal->id) }}" method="POST" @submit.prevent="submitAjaxForm($event)"
            data-autosave data-autosave-key="jurnal-edit-{{ $jurnal->id }}" class="flex flex-col">
            @csrf
            @method('PUT')
            <div class="flex flex-col gap-4 p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">No. Jurnal</span></label>
                        <input type="text" class="input input-bordered bg-base-200" name="no_jurnal" value="{{ $jurnal->no_jurnal }}" readonly>
                        <span class="label-text-alt mt-1 text-base-content/50">Nomor jurnal tidak dapat diubah.</span>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Tanggal Jurnal <span class="text-error">*</span></span></label>
                        <input type="date" class="input input-bordered" name="tanggal" value="{{ $jurnal->tanggal->format('Y-m-d') }}" required>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Sumber Transaksi</span></label>
                        <input type="text" class="input input-bordered bg-base-200" value="MANUAL" readonly>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Reff ID / ID Referensi</span></label>
                        <input type="text" class="input input-bordered bg-base-200" value="Tidak digunakan untuk jurnal manual" readonly>
                    </div>
                </div>
                <div>
                    <h6 class="mb-2 font-bold">Baris Jurnal</h6>
                    <div class="flex flex-col gap-2">
                        @foreach ($jurnal->details as $i => $detail)
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-11">
                                <select class="select select-bordered md:col-span-5" data-app-picker data-placeholder="Cari kode atau nama akun..." name="details[{{ $i }}][coa_id]" required>
                                    @foreach ($coas as $coa)
                                        <option value="{{ $coa->id }}" @selected($detail->coa_id == $coa->id)>{{ $coa->kode_akun }} — {{ $coa->nama_akun }}</option>
                                    @endforeach
                                </select>
                                <input class="input input-bordered text-end md:col-span-3" type="number" min="0" step="0.01" name="details[{{ $i }}][debit]" value="{{ $detail->debit }}" data-money-input>
                                <input class="input input-bordered text-end md:col-span-3" type="number" min="0" step="0.01" name="details[{{ $i }}][kredit]" value="{{ $detail->kredit }}" data-money-input>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Keterangan / Catatan</span></label>
                    <textarea class="textarea textarea-bordered" name="keterangan" rows="3">{{ $jurnal->keterangan }}</textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-warning"><i class="fa-solid fa-floppy-disk"></i> Perbarui Header</button>
            </div>
        </form>
    </div>
</dialog>
