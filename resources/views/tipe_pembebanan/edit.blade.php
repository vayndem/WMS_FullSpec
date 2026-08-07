<div class="modal fade" id="editTipePembebananModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title fw-bold text-white">
                    <i class="fa-solid fa-pen-to-square me-2"></i>Edit Tipe Pembebanan
                </h5>
                <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-update-tipe-pembebanan" action="{{ route('tipe-pembebanan.update', $tipe->id) }}"
                method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3 mb-3">
                        <label class="fw-bold">Nama Tipe <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_tipe" value="{{ $tipe->nama_tipe }}"
                            required>
                    </div>
                    <div class="mb-3 mb-0">
                        <label class="fw-bold">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="3">{{ $tipe->keterangan }}</textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white fw-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
