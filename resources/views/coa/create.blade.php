<dialog id="createCoaModal" class="modal">
    <div class="modal-box max-w-2xl p-0 overflow-hidden">
        <div class="bg-primary px-6 py-4 text-primary-content">
            <h3 class="text-lg font-bold"><i class="fa-solid fa-sitemap"></i> Tambah Akun COA</h3>
        </div>
        <form action="{{ route('chart-of-accounts.store') }}" method="POST" @submit.prevent="submitAjaxForm($event)" class="flex flex-col">
            @csrf
            <div class="flex flex-col gap-4 p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Kode Akun <span class="text-error">*</span></span></label>
                        <input type="text" class="input input-bordered" name="kode_akun" placeholder="Contoh: 1101" required>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Nama Akun <span class="text-error">*</span></span></label>
                        <input type="text" class="input input-bordered" name="nama_akun" placeholder="Contoh: Kas Utama" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Kategori Akun <span class="text-error">*</span></span></label>
                        <select class="select select-bordered" name="kategori_akun" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="ASET">ASET</option>
                            <option value="LIABILITAS">LIABILITAS</option>
                            <option value="EKUITAS">EKUITAS</option>
                            <option value="PENDAPATAN">PENDAPATAN</option>
                            <option value="BEBAN">BEBAN</option>
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Posisi Normal <span class="text-error">*</span></span></label>
                        <select class="select select-bordered" name="posisi_normal" required>
                            <option value="">-- Pilih Posisi --</option>
                            <option value="DEBIT">DEBIT</option>
                            <option value="KREDIT">KREDIT</option>
                        </select>
                    </div>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Keterangan</span></label>
                    <textarea class="textarea textarea-bordered" name="keterangan" rows="3" placeholder="Opsi catatan atau deskripsi akun..."></textarea>
                </div>
                <div class="flex flex-wrap gap-6">
                    <input type="hidden" name="is_active" value="0">
                    <label class="label cursor-pointer gap-2"><input type="checkbox" class="checkbox" name="is_active" value="1" checked><span class="label-text">Aktif</span></label>
                    <input type="hidden" name="is_postable" value="0">
                    <label class="label cursor-pointer gap-2"><input type="checkbox" class="checkbox" name="is_postable" value="1" checked><span class="label-text">Boleh diposting</span></label>
                    <input type="hidden" name="is_cash_bank" value="0">
                    <label class="label cursor-pointer gap-2"><input type="checkbox" class="checkbox" name="is_cash_bank" value="1"><span class="label-text">Kas/Bank</span></label>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
            </div>
        </form>
    </div>
</dialog>
