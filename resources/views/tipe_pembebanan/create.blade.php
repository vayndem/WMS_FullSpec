<div class="modal fade" id="createTipePembebananModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-create" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold text-white">
                    <i class="fa-solid fa-tags me-2"></i>Tambah Tipe Pembebanan
                </h5>
                <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-store-tipe-pembebanan" action="{{ route('tipe-pembebanan.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3 mb-3">
                        <label class="fw-bold">Nama Tipe <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_tipe"
                            placeholder="Contoh: DIRECT_COST / INDIRECT_COST" required>
                    </div>
                    <div class="mb-3 mb-0">
                        <label class="fw-bold">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="3" placeholder="Opsi keterangan penjelasan tipe..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
