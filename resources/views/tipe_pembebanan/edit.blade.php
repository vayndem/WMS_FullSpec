<dialog id="editTipePembebananModal" class="modal">
    <div class="modal-box max-w-lg p-0 overflow-hidden">
        <div class="bg-warning px-6 py-4 text-warning-content">
            <h3 class="text-lg font-bold"><i class="fa-solid fa-pen-to-square"></i> Edit Tipe Pembebanan</h3>
        </div>
        <form action="{{ route('tipe-pembebanan.update', $tipe->id) }}" method="POST" @submit.prevent="submitAjaxForm($event)" class="flex flex-col">
            @csrf
            @method('PUT')
            <div class="flex flex-col gap-4 p-6">
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Nama Tipe <span class="text-error">*</span></span></label>
                    <input type="text" name="nama_tipe" class="input input-bordered" value="{{ $tipe->nama_tipe }}" required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Keterangan</span></label>
                    <textarea name="keterangan" class="textarea textarea-bordered" rows="3">{{ $tipe->keterangan }}</textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-warning">
                    <i class="fa-solid fa-floppy-disk"></i> Perbarui
                </button>
            </div>
        </form>
    </div>
</dialog>
