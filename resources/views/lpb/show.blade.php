<div class="modal fade" id="showLpbModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-circle-info me-2"></i>Detail
                    LPB: {{ $lpb->id_lpb }}</h5>
                <button type="button" class="btn-close btn-close-white " data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Nomor PO</small>
                        <span class="fw-bold text-primary h6">{{ $lpb->no_po }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Tanggal Terima</small>
                        <span class="fw-bold h6">{{ $lpb->tanggal }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">No. Surat Jalan</small>
                        <span class="fw-bold h6">{{ $lpb->no_sj }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Supplier</small>
                        <span class="fw-bold h6">{{ $lpb->pembelian->supplier->nama ?? '-' }}</span>
                    </div>
                </div>

                <hr>
                <h6 class="fw-bold text-dark mb-3"><i
                        class="fa-solid fa-boxes-stacked me-2 text-info"></i>Daftar Barang Diterima</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="bg-light text-uppercase font-size-12">
                            <tr>
                                <th width="5%" class="text-center">#</th>
                                <th>Nama Bahan</th>
                                <th width="18%" class="text-center">Kategori</th>
                                <th width="15%" class="text-center">Lot Number</th>
                                <th width="15%" class="text-center">Qty Diterima</th>
                                <th width="15%" class="text-end">Harga Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lpb->details as $index => $detail)
                                <tr>
                                    <td class="text-center align-middle">{{ $index + 1 }}</td>
                                    <td class="align-middle fw-bold">{{ $detail->bahan->nama ?? '-' }}</td>
                                    <td class="text-center align-middle">{{ $detail->kategori->katnama ?? '-' }}</td>
                                    <td class="text-center align-middle">{{ $detail->lot_number ?? '-' }}</td>
                                    <td class="text-center align-middle fw-bold text-success">
                                        {{ $detail->jumlah_barang_diterima }}</td>
                                    <td class="text-end align-middle">
                                        {{ $detail->harga ? 'Rp ' . number_format($detail->harga, 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Tidak ada data detail.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
