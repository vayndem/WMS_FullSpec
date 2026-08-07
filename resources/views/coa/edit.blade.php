<div class="modal fade" id="editCoaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title fw-bold text-white">
                    <i class="fa-solid fa-pen-to-square me-2"></i>Edit Akun COA
                </h5>
                <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-update-coa" action="{{ route('chart-of-accounts.update', $coa->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Kode Akun <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="kode_akun" value="{{ $coa->kode_akun }}"
                                required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Nama Akun <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_akun" value="{{ $coa->nama_akun }}"
                                required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Kategori Akun <span class="text-danger">*</span></label>
                            <select class="form-control" name="kategori_akun" required>
                                <option value="ASET" {{ $coa->kategori_akun === 'ASET' ? 'selected' : '' }}>ASET
                                </option>
                                <option value="LIABILITAS" {{ $coa->kategori_akun === 'LIABILITAS' ? 'selected' : '' }}>
                                    LIABILITAS</option>
                                <option value="EKUITAS" {{ $coa->kategori_akun === 'EKUITAS' ? 'selected' : '' }}>
                                    EKUITAS</option>
                                <option value="PENDAPATAN" {{ $coa->kategori_akun === 'PENDAPATAN' ? 'selected' : '' }}>
                                    PENDAPATAN</option>
                                <option value="BEBAN" {{ $coa->kategori_akun === 'BEBAN' ? 'selected' : '' }}>BEBAN
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Posisi Normal <span class="text-danger">*</span></label>
                            <select class="form-control" name="posisi_normal" required>
                                <option value="DEBIT" {{ $coa->posisi_normal === 'DEBIT' ? 'selected' : '' }}>DEBIT
                                </option>
                                <option value="KREDIT" {{ $coa->posisi_normal === 'KREDIT' ? 'selected' : '' }}>KREDIT
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 mb-0">
                        <label class="fw-bold">Keterangan</label>
                        <textarea class="form-control" name="keterangan" rows="3">{{ $coa->keterangan }}</textarea>
                    </div>
                    <div class="row g-2">
                        @foreach (['is_active' => 'Aktif', 'is_postable' => 'Boleh diposting', 'is_cash_bank' => 'Kas/Bank'] as $field => $label)
                            <div class="col-md-4 form-check"><input type="hidden" name="{{ $field }}"
                                    value="0"><input class="form-check-input" type="checkbox"
                                    name="{{ $field }}" value="1" id="edit-{{ $field }}"
                                    @checked($coa->{$field})><label class="form-check-label"
                                    for="edit-{{ $field }}">{{ $label }}</label></div>
                        @endforeach
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
