<div class="modal fade" id="supplierCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-create" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="bg-white text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-sm"
                        style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-truck-field fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0">Tambah Supplier Baru</h5>
                        <small class="text-white-50">Isi formulir di bawah ini untuk menambahkan data vendor
                            baru</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white  opacity-8" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form action="{{ route('supplier.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4 bg-light">
                    <div class="card border-0 shadow-sm p-3 mb-0">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Nama Supplier <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="d-flex">
                                        <span class="input-group-text bg-white border-right-0"><i
                                                class="fa-solid fa-building text-muted"></i></span>
                                    </div>
                                    <input type="text" name="nama" class="form-control border-left-0"
                                        placeholder="Masukkan nama supplier..." required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">UP (Contact Person) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="d-flex">
                                        <span class="input-group-text bg-white border-right-0"><i
                                                class="fa-solid fa-user text-muted"></i></span>
                                    </div>
                                    <input type="text" name="up" class="form-control border-left-0"
                                        placeholder="Nama penanggung jawab..." required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">No. Telepon <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="d-flex">
                                        <span class="input-group-text bg-white border-right-0"><i
                                                class="fa-solid fa-phone text-muted"></i></span>
                                    </div>
                                    <input type="text" name="telp" class="form-control border-left-0"
                                        placeholder="Nomor telepon..." required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">NPWP</label>
                                <div class="input-group">
                                    <div class="d-flex">
                                        <span class="input-group-text bg-white border-right-0"><i
                                                class="fa-solid fa-id-card text-muted"></i></span>
                                    </div>
                                    <input type="text" name="npwp" class="form-control border-left-0"
                                        placeholder="Nomor NPWP...">
                                </div>
                            </div>

                            <div class="col-md-6 mb-3 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Sistem Pembayaran <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="d-flex">
                                        <span class="input-group-text bg-white border-right-0"><i
                                                class="fa-solid fa-credit-card text-muted"></i></span>
                                    </div>
                                    <input type="text" name="pembayaran" class="form-control border-left-0"
                                        placeholder="Contoh: Transfer / COD / Tempo 30 Hari..." required>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3 mb-3">
                                <label class="fw-bold text-dark small text-uppercase">Alamat <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="d-flex">
                                        <span class="input-group-text bg-white border-right-0"><i
                                                class="fa-solid fa-location-dot text-muted"></i></span>
                                    </div>
                                    <textarea name="alamat" class="form-control border-left-0" rows="1" placeholder="Masukkan alamat lengkap..."
                                        required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0 px-4 py-3">
                    <button type="button" class="btn btn-light border px-4 fw-bold"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary shadow-sm px-4 fw-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
