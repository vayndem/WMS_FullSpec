<dialog id="kategoriBahanModal" class="modal">
    <div class="modal-box max-w-4xl p-0 overflow-hidden">
        <div class="flex items-center justify-between bg-primary px-6 py-4 text-primary-content">
            <h3 class="text-lg font-bold">{{ $kategori->exists ? 'Edit' : 'Tambah' }} Kategori Bahan</h3>
        </div>
        <form action="{{ $kategori->exists ? route('kategori-bahan.update', $kategori) : route('kategori-bahan.store') }}"
            method="POST" @submit.prevent="submitAjaxForm($event)" class="flex flex-col">
            @csrf
            @if ($kategori->exists)
                @method('PUT')
            @endif
            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Nama Kategori</span></label>
                    <input type="text" class="input input-bordered" name="katnama" value="{{ $kategori->katnama }}" required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Tipe Pembebanan</span></label>
                    <select data-app-picker data-placeholder="Cari tipe pembebanan..." class="select select-bordered" name="tipe_pembebanan_id">
                        <option value="">Pilih tipe</option>
                        @foreach ($tipePembebanans as $tipe)
                            <option value="{{ $tipe->id }}" @selected($kategori->tipe_pembebanan_id == $tipe->id)>{{ $tipe->nama_tipe }}</option>
                        @endforeach
                    </select>
                </div>
                @foreach (['coa_persediaan_id' => 'Akun Persediaan', 'coa_beban_id' => 'Akun Pemakaian/Beban', 'coa_clearing_lpb_id' => 'Akun GRNI', 'coa_beban_selisih_opname_id' => 'Beban Selisih Opname', 'coa_koreksi_opname_id' => 'Koreksi Positif Opname'] as $field => $label)
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">{{ $label }}</span></label>
                        <select data-app-picker data-placeholder="Cari kode atau nama akun..." class="select select-bordered" name="{{ $field }}" required>
                            <option value="">Pilih akun</option>
                            @foreach ($coas as $coa)
                                <option value="{{ $coa->id }}" @selected($kategori->{$field} == $coa->id)>{{ $coa->kode_akun }} — {{ $coa->nama_akun }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</dialog>
