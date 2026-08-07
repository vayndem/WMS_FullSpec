<div class="modal fade" id="createJurnalModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-create" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold text-white">
                    <i class="fa-solid fa-book-bookmark me-2"></i>Buat Header Jurnal Baru
                </h5>
                <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-store-jurnal" action="{{ route('jurnal.store') }}" method="POST" data-autosave
                data-autosave-key="jurnal-create">
                @csrf
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">No. Jurnal</label>
                            <input type="text" class="form-control" name="no_jurnal" value="{{ $documentNumber }}" readonly>
                            <small class="text-muted">Kode finansial internal dengan penanda JR.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Tanggal Jurnal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal" value="{{ date('Y-m-d') }}"
                                required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Sumber Transaksi</label>
                            <input type="text" class="form-control" name="sumber_transaksi" value="MANUAL" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Reff ID / ID Referensi</label>
                            <input type="number" class="form-control" name="reff_id"
                                placeholder="ID Dokumen Asal (Opsional)">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Keterangan / Catatan</label>
                        <textarea class="form-control" name="keterangan" rows="3" placeholder="Opsi keterangan transaksi..."></textarea>
                    </div>
                    <h6>Baris jurnal</h6>
                    <div id="journal-lines">
                        @foreach ([0, 1] as $i)
                            <div class="row g-2 mb-2 journal-line">
                                <div class="col-md-5"><select class="form-select" data-app-picker
                                        data-placeholder="Cari kode atau nama akun..."
                                        name="details[{{ $i }}][coa_id]" required>
                                        <option value="">Pilih akun</option>
                                        @foreach ($coas as $coa)
                                            <option value="{{ $coa->id }}">{{ $coa->kode_akun }} —
                                                {{ $coa->nama_akun }}</option>
                                        @endforeach
                                    </select></div>
                                <div class="col-md-3"><input class="form-control text-end" type="number" min="0"
                                        step="0.01" name="details[{{ $i }}][debit]"
                                        value="{{ $i === 0 ? '' : 0 }}" placeholder="Debit"></div>
                                <div class="col-md-3"><input class="form-control text-end" type="number" min="0"
                                        step="0.01" name="details[{{ $i }}][kredit]"
                                        value="{{ $i === 1 ? '' : 0 }}" placeholder="Kredit"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Header
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
