<dialog id="editCoaModal" class="modal">
    <div class="modal-box max-w-2xl p-0 overflow-hidden">
        <div class="bg-warning px-6 py-4 text-warning-content">
            <h3 class="text-lg font-bold"><i class="fa-solid fa-pen-to-square"></i> Edit Akun COA</h3>
        </div>
        <form action="{{ route('bagan-akun.update', $coa->id) }}" method="POST" @submit.prevent="submitAjaxForm($event)" class="flex flex-col">
            @csrf
            @method('PUT')
            <div class="flex flex-col gap-4 p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Kode Akun <span class="text-error">*</span></span></label>
                        <input type="text" class="input input-bordered" name="kode_akun" value="{{ $coa->kode_akun }}" required>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Nama Akun <span class="text-error">*</span></span></label>
                        <input type="text" class="input input-bordered" name="nama_akun" value="{{ $coa->nama_akun }}" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Kategori Akun <span class="text-error">*</span></span></label>
                        <select class="select select-bordered" name="kategori_akun" required>
                            @foreach (['ASET', 'LIABILITAS', 'EKUITAS', 'PENDAPATAN', 'BEBAN'] as $option)
                                <option value="{{ $option }}" {{ $coa->kategori_akun === $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Posisi Normal <span class="text-error">*</span></span></label>
                        <select class="select select-bordered" name="posisi_normal" required>
                            <option value="DEBIT" {{ $coa->posisi_normal === 'DEBIT' ? 'selected' : '' }}>DEBIT</option>
                            <option value="KREDIT" {{ $coa->posisi_normal === 'KREDIT' ? 'selected' : '' }}>KREDIT</option>
                        </select>
                    </div>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Klasifikasi Fiskal</span></label>
                    <select name="klasifikasi_fiskal" class="select select-bordered">
                        @foreach (\App\Models\BaganAkun::KLASIFIKASI_FISKAL as $kode => $label)
                            <option value="{{ $kode }}" @selected($coa->klasifikasi_fiskal === $kode)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="label-text-alt mt-1 text-base-content/50">Dipakai laporan Rekonsiliasi Fiskal untuk menentukan koreksi laba komersial ke laba fiskal.</span>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Keterangan</span></label>
                    <textarea class="textarea textarea-bordered" name="keterangan" rows="3">{{ $coa->keterangan }}</textarea>
                </div>
                <div class="flex flex-wrap gap-6">
                    @foreach (['is_active' => 'Aktif', 'is_postable' => 'Boleh diposting', 'is_cash_bank' => 'Kas/Bank'] as $field => $label)
                        <div>
                            <input type="hidden" name="{{ $field }}" value="0">
                            <label class="label cursor-pointer gap-2">
                                <input type="checkbox" class="checkbox" name="{{ $field }}" value="1" @checked($coa->{$field})>
                                <span class="label-text">{{ $label }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-warning"><i class="fa-solid fa-floppy-disk"></i> Perbarui</button>
            </div>
        </form>
    </div>
</dialog>
