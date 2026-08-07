<div class="modal fade" id="editJurnalModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title fw-bold text-white">
                    <i class="fa-solid fa-pen-to-square me-2"></i>Edit Header Jurnal
                </h5>
                <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-update-jurnal" action="{{ route('jurnal.update', $jurnal->id) }}" method="POST"
                data-autosave data-autosave-key="jurnal-edit-{{ $jurnal->id }}">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">No. Jurnal</label>
                            <input type="text" class="form-control" name="no_jurnal" value="{{ $jurnal->no_jurnal }}" readonly>
                            <small class="text-muted">Nomor jurnal tidak dapat diubah.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Tanggal Jurnal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal" value="{{ $jurnal->tanggal }}"
                                required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Sumber Transaksi</label>
                            <input type="text" class="form-control" value="MANUAL" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold">Reff ID / ID Referensi</label>
                            <input type="text" class="form-control" value="Tidak digunakan untuk jurnal manual"
                                readonly>
                        </div>
                    </div>
                    <h6>Baris jurnal</h6>
                    @foreach ($jurnal->details as $i => $detail)
                        <div class="row g-2 mb-2">
                            <div class="col-md-5"><select class="form-select"
                                    name="details[{{ $i }}][coa_id]" required>
                                    @foreach ($coas as $coa)
                                        <option value="{{ $coa->id }}" @selected($detail->coa_id == $coa->id)>
                                            {{ $coa->kode_akun }} — {{ $coa->nama_akun }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-3"><input class="form-control text-end" type="number" min="0"
                                    step=".01" name="details[{{ $i }}][debit]"
                                    value="{{ $detail->debit }}"></div>
                            <div class="col-md-3"><input class="form-control text-end" type="number" min="0"
                                    step=".01" name="details[{{ $i }}][kredit]"
                                    value="{{ $detail->kredit }}"></div>
                        </div>
                    @endforeach
                    <div class="mb-3 mb-0">
                        <label class="fw-bold">Keterangan / Catatan</label>
                        <textarea class="form-control" name="keterangan" rows="3">{{ $jurnal->keterangan }}</textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white fw-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Perbarui Header
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
