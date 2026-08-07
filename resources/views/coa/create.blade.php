<div class="modal fade" id="createCoaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-create" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold text-white">
                    <i class="fa-solid fa-sitemap me-2"></i>Tambah Akun COA
                </h5>
                <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-store-coa" action="{{ route('chart-of-accounts.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Kode Akun <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kode_akun" placeholder="Contoh: 1101"
                                required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Nama Akun <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_akun" placeholder="Contoh: Kas Utama"
                                required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Kategori Akun <span class="text-danger">*</span></label>
                            <select class="form-control" name="kategori_akun" required>
                                <option value="">-- Pilih Kategori --</option>
                                <option value="ASET">ASET</option>
                                <option value="LIABILITAS">LIABILITAS</option>
                                <option value="EKUITAS">EKUITAS</option>
                                <option value="PENDAPATAN">PENDAPATAN</option>
                                <option value="BEBAN">BEBAN</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Posisi Normal <span class="text-danger">*</span></label>
                            <select class="form-control" name="posisi_normal" required>
                                <option value="">-- Pilih Posisi --</option>
                                <option value="DEBIT">DEBIT</option>
                                <option value="KREDIT">KREDIT</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 mb-0">
                        <label class="fw-bold">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="3" placeholder="Opsi catatan atau deskripsi akun..."></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4 form-check"><input type="hidden" name="is_active" value="0"><input
                                class="form-check-input" type="checkbox" name="is_active" value="1" checked
                                id="coa-active"><label class="form-check-label" for="coa-active">Aktif</label></div>
                        <div class="col-md-4 form-check"><input type="hidden" name="is_postable" value="0"><input
                                class="form-check-input" type="checkbox" name="is_postable" value="1" checked
                                id="coa-postable"><label class="form-check-label" for="coa-postable">Boleh
                                diposting</label></div>
                        <div class="col-md-4 form-check"><input type="hidden" name="is_cash_bank" value="0"><input
                                class="form-check-input" type="checkbox" name="is_cash_bank" value="1"
                                id="coa-cash"><label class="form-check-label" for="coa-cash">Kas/Bank</label></div>
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
