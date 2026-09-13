<dialog id="userCreateModal" class="modal">
    <div class="modal-box max-w-2xl overflow-hidden p-0">
        <div class="flex items-center gap-3 bg-primary px-6 py-4 text-primary-content">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold">Tambah Pengguna</h3>
                <p class="text-sm text-primary-content/80">Akun baru langsung berstatus aktif dan dapat login</p>
            </div>
        </div>
        <form action="{{ route('user.store') }}" method="POST" class="flex flex-col">
            @csrf
            <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Nama Lengkap <span class="text-error">*</span></span></label>
                    <div class="join">
                        <span class="join-item btn btn-disabled btn-outline"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="name" class="input input-bordered join-item flex-1" placeholder="Nama pengguna..." required>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Email <span class="text-error">*</span></span></label>
                    <div class="join">
                        <span class="join-item btn btn-disabled btn-outline"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" class="input input-bordered join-item flex-1" placeholder="nama@perusahaan.com" required>
                    </div>
                </div>

                <div class="form-control sm:col-span-2">
                    <label class="label"><span class="label-text font-semibold">Peran <span class="text-error">*</span></span></label>
                    <select name="type" class="select select-bordered" data-app-picker required>
                        <option value="">Pilih peran pengguna...</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ \App\Models\User::ROLE_NAMES[$role->id] ?? $role->name }}</option>
                        @endforeach
                    </select>
                    <span class="label-text-alt mt-1 text-base-content/60">Peran menentukan menu, policy, dan gudang yang dapat diakses.</span>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Password <span class="text-error">*</span></span></label>
                    <div class="join">
                        <span class="join-item btn btn-disabled btn-outline"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" class="input input-bordered join-item flex-1" placeholder="Minimal 8 karakter" required>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Ulangi Password <span class="text-error">*</span></span></label>
                    <div class="join">
                        <span class="join-item btn btn-disabled btn-outline"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password_confirmation" class="input input-bordered join-item flex-1" placeholder="Ulangi password" required>
                    </div>
                </div>

                <div role="alert" class="alert alert-info sm:col-span-2">
                    <i class="fa-solid fa-circle-info"></i>
                    <span class="text-sm">Password wajib minimal 8 karakter serta memuat huruf dan angka.</span>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</dialog>
