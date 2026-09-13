<dialog id="userEditModal" class="modal">
    <div class="modal-box max-w-2xl overflow-hidden p-0">
        <div class="flex items-center gap-3 bg-warning px-6 py-4 text-warning-content">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20">
                <i class="fa-solid fa-user-pen"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold">Ubah Pengguna</h3>
                <p class="text-sm text-warning-content/80">{{ $pengguna->email }}</p>
            </div>
        </div>
        <form action="{{ route('user.update', $pengguna) }}" method="POST" class="flex flex-col">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">
                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Nama Lengkap <span class="text-error">*</span></span></label>
                    <div class="join">
                        <span class="join-item btn btn-disabled btn-outline"><i class="fa-solid fa-user"></i></span>
                        <input type="text" name="name" value="{{ old('name', $pengguna->name) }}" class="input input-bordered join-item flex-1" required>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Email <span class="text-error">*</span></span></label>
                    <div class="join">
                        <span class="join-item btn btn-disabled btn-outline"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" value="{{ old('email', $pengguna->email) }}" class="input input-bordered join-item flex-1" required>
                    </div>
                </div>

                <div class="form-control sm:col-span-2">
                    <label class="label"><span class="label-text font-semibold">Peran <span class="text-error">*</span></span></label>
                    <select name="type" class="select select-bordered" data-app-picker required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('type', $pengguna->type) == $role->id)>
                                {{ \App\Models\User::ROLE_NAMES[$role->id] ?? $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Password Baru</span></label>
                    <div class="join">
                        <span class="join-item btn btn-disabled btn-outline"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" class="input input-bordered join-item flex-1" placeholder="Kosongkan bila tidak diubah">
                    </div>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-semibold">Ulangi Password Baru</span></label>
                    <div class="join">
                        <span class="join-item btn btn-disabled btn-outline"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password_confirmation" class="input input-bordered join-item flex-1" placeholder="Ulangi password baru">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <div class="stats stats-vertical w-full border border-base-300 sm:stats-horizontal">
                        <div class="stat py-3">
                            <div class="stat-title text-xs">Status Akun</div>
                            <div class="stat-value text-base {{ $pengguna->is_active ? 'text-success' : 'text-error' }}">
                                {{ $pengguna->is_active ? 'Aktif' : 'Nonaktif' }}
                            </div>
                        </div>
                        <div class="stat py-3">
                            <div class="stat-title text-xs">Login Terakhir</div>
                            <div class="stat-value text-base">{{ $pengguna->last_login_at?->translatedFormat('d M Y H:i') ?? 'Belum pernah' }}</div>
                            <div class="stat-desc">{{ $pengguna->last_login_ip ?? 'tanpa catatan IP' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-base-300 bg-base-100 px-6 py-4">
                <button type="button" class="btn btn-ghost" onclick="closeAjaxModal(this)">Batal</button>
                <button type="submit" class="btn btn-warning">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</dialog>
