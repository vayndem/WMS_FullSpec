<dialog id="createTipePembebananModal" class="modal">
    <div class="modal-box max-w-lg p-0 overflow-hidden">
        <div class="bg-primary px-6 py-4 text-primary-content">
            <h3 class="text-lg font-bold"><i class="fa-solid fa-tags"></i> Tambah Tipe Pembebanan</h3>
        </div>
        <form action="{{ route('tipe-pembebanan.store') }}" method="POST" @submit.prevent="submitAjaxForm($event)" class="flex flex-col">
            @csrf
            <div class="flex flex-col gap-4 p-6">
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Nama Tipe <span class="text-error">*</span></span></label>
                    <input type="text" name="nama_tipe" class="input input-bordered" placeholder="Contoh: DIRECT_COST / INDIRECT_COST" required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Keterangan</span></label>
                    <textarea name="keterangan" class="textarea textarea-bordered" rows="3" placeholder="Opsi keterangan penjelasan tipe..."></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</dialog>
