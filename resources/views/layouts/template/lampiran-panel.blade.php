@php
    $lampiranInduk = $induk;
    $lampiranTipe = $tipe;
    $daftar = $lampiranInduk->lampiran ?? collect();
    $bolehUnggah = $bolehUnggah ?? auth()->user()->can('view', $lampiranInduk);
    $kategoriTersedia = $kategori ?? array_keys(\App\Models\LampiranDokumen::KATEGORI);
@endphp

<div class="card border border-base-300 bg-base-100 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-base-300 p-4">
        <h5 class="font-bold"><i class="fa-solid fa-paperclip text-primary"></i> Lampiran Dokumen</h5>
        <span class="badge badge-ghost badge-sm">{{ $daftar->count() }} berkas</span>
    </div>

    <div class="divide-y divide-base-300">
        @forelse ($daftar as $berkas)
            <div class="flex flex-wrap items-center gap-3 p-3">
                <i class="fa-solid {{ $berkas->ikon }} text-xl"></i>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold">{{ $berkas->nama_asli }}</p>
                    <p class="text-xs text-base-content/60">
                        {{ $berkas->label_kategori }} &middot; {{ $berkas->ukuran_terbaca }}
                        &middot; {{ $berkas->created_at->translatedFormat('d M Y H:i') }}
                        @if ($berkas->user)
                            &middot; {{ $berkas->user->name }}
                        @endif
                    </p>
                    @if ($berkas->keterangan)
                        <p class="text-xs italic text-base-content/50">{{ $berkas->keterangan }}</p>
                    @endif
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <a href="{{ route('lampiran.download', $berkas) }}" class="btn btn-ghost btn-sm btn-square text-info" title="Unduh">
                        <i class="fa-solid fa-download"></i>
                    </a>
                    @can('delete', $berkas)
                        <form action="{{ route('lampiran.destroy', $berkas) }}" method="POST"
                            x-data @submit.prevent="AppAlert.confirm('Hapus lampiran ini secara permanen?').then(r => r.isConfirmed && $el.submit())">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm btn-square text-error" title="Hapus">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        @empty
            <div class="p-6 text-center text-base-content/50">
                <i class="fa-solid fa-file-circle-plus text-2xl"></i>
                <p class="mt-2 text-sm">Belum ada lampiran. Unggah bukti dokumen agar jejak audit lengkap.</p>
            </div>
        @endforelse
    </div>

    @if ($bolehUnggah)
        <form action="{{ route('lampiran.store') }}" method="POST" enctype="multipart/form-data"
            class="border-t border-base-300 p-4">
            @csrf
            <input type="hidden" name="lampiran_type" value="{{ $lampiranTipe }}">
            <input type="hidden" name="lampiran_id" value="{{ $lampiranInduk->getKey() }}">

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="form-control lg:col-span-2">
                    <label class="label"><span class="label-text text-sm font-semibold">Berkas</span></label>
                    <input type="file" name="berkas" class="file-input file-input-bordered file-input-sm w-full" required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-sm font-semibold">Kategori</span></label>
                    <select name="kategori" class="select select-bordered select-sm" required>
                        @foreach ($kategoriTersedia as $kode)
                            <option value="{{ $kode }}">{{ \App\Models\LampiranDokumen::KATEGORI[$kode] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-sm font-semibold">Keterangan</span></label>
                    <input type="text" name="keterangan" class="input input-bordered input-sm" placeholder="Opsional">
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <span class="text-xs text-base-content/60">
                    Maksimal {{ \App\Services\LampiranService::UKURAN_MAKS_KB / 1024 }} MB &middot;
                    {{ implode(', ', \App\Services\LampiranService::MIME_DIIZINKAN) }}
                </span>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-upload"></i> Unggah Lampiran
                </button>
            </div>
        </form>
    @endif
</div>
