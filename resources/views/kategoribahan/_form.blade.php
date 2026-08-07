<div class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-create">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $kategori->exists ? 'Edit' : 'Tambah' }} Kategori Bahan</h5><button
                    class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form class="category-form"
                action="{{ $kategori->exists ? route('kategori-bahan.update', $kategori) : route('kategori-bahan.store') }}"
                method="POST">@csrf @if ($kategori->exists)
                    @method('PUT')
                @endif
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nama kategori</label><input class="form-control"
                                name="katnama" value="{{ $kategori->katnama }}" required></div>
                        <div class="col-md-6"><label class="form-label">Tipe pembebanan</label><select data-app-picker
                                data-placeholder="Cari tipe pembebanan..."
                                class="form-select" name="tipe_pembebanan_id">
                                <option value="">Pilih tipe</option>
                                @foreach ($tipePembebanans as $tipe)
                                    <option value="{{ $tipe->id }}" @selected($kategori->tipe_pembebanan_id == $tipe->id)>
                                        {{ $tipe->nama_tipe }}</option>
                                @endforeach
                            </select>
                        </div>
                        @foreach (['coa_persediaan_id' => 'Akun Persediaan', 'coa_beban_id' => 'Akun Pemakaian/Beban', 'coa_clearing_lpb_id' => 'Akun GRNI', 'coa_beban_selisih_opname_id' => 'Beban Selisih Opname', 'coa_koreksi_opname_id' => 'Koreksi Positif Opname'] as $field => $label)
                            <div class="col-md-4"><label class="form-label">{{ $label }}</label><select data-app-picker
                                    data-placeholder="Cari kode atau nama akun..."
                                    class="form-select" name="{{ $field }}" required>
                                    <option value="">Pilih akun</option>
                                    @foreach ($coas as $coa)
                                        <option value="{{ $coa->id }}" @selected($kategori->{$field} == $coa->id)>
                                            {{ $coa->kode_akun }} — {{ $coa->nama_akun }}</option>
                                    @endforeach
                                </select></div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light"
                        data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
