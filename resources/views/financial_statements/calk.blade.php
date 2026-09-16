@extends('layouts.app')

@section('content')
    <div class="content-page">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold">Catatan atas Laporan Keuangan</h3>
                <p class="text-base-content/60">Rincian angka ditarik otomatis dari buku besar; bagian naratif disimpan per periode dan bisa direvisi.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('financial-statements.calk.pdf', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-error btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('financial-statements.calk.excel', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
            </div>
        </div>

        @include('financial_statements._tabs')

        @if (session('success'))
            <div class="alert alert-success mb-4">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error mb-4">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-base-300 p-4">
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Dari</span></label>
                    <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text text-xs font-semibold uppercase">Sampai</span></label>
                    <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="input input-bordered input-sm">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
            </form>

            @php
                $rupiah = fn ($nilai) => 'Rp ' . number_format($nilai, 0, ',', '.');
            @endphp

            <form method="POST" action="{{ route('financial-statements.calk.simpan') }}" class="space-y-4 border-b border-base-300 p-4">
                @csrf
                <input type="hidden" name="from" value="{{ $from->format('Y-m-d') }}">
                <input type="hidden" name="to" value="{{ $to->format('Y-m-d') }}">

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h4 class="text-lg font-semibold">Bagian Naratif</h4>
                    @if ($data['catatan']?->penyusun)
                        <span class="text-xs text-base-content/50">
                            Terakhir disimpan oleh {{ $data['catatan']->penyusun->name }} pada {{ $data['catatan']->updated_at?->format('d-m-Y H:i') }}
                        </span>
                    @endif
                </div>

                @foreach ($data['naratif'] as $kunci => $bagian)
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-semibold">{{ $bagian['judul'] }}</span>
                            @if ($bagian['bawaan'])
                                <span class="label-text-alt"><span class="badge badge-ghost badge-sm">template bawaan, belum disimpan</span></span>
                            @endif
                        </label>
                        <textarea name="{{ $kunci }}" rows="{{ $kunci === 'kebijakan_akuntansi' ? 12 : 4 }}" class="textarea textarea-bordered font-mono text-sm">{{ old($kunci, $bagian['isi']) }}</textarea>
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Catatan
                </button>
            </form>

            <div class="space-y-6 p-4">
                <h4 class="text-lg font-semibold">Rincian dari Buku Besar</h4>

                @foreach ($data['rincian'] as $bagian)
                    <div>
                        <h6 class="mb-2 font-bold text-primary">{{ $bagian['judul'] }}</h6>
                        <div class="overflow-x-auto">
                            <table class="table table-sm">
                                <tbody>
                                    @forelse ($bagian['baris'] as $baris)
                                        <tr>
                                            <td>
                                                {{ $baris['label'] }}
                                                @isset($baris['catatan'])
                                                    <div class="text-xs text-base-content/50">{{ $baris['catatan'] }}</div>
                                                @endisset
                                            </td>
                                            <td class="text-end {{ $baris['nilai'] < 0 ? 'text-error' : '' }}">{{ $rupiah($baris['nilai']) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="py-3 text-center text-base-content/50">Tidak ada saldo pada bagian ini.</td></tr>
                                    @endforelse
                                    <tr class="bg-base-200/40 font-bold">
                                        <td class="text-end">Jumlah</td>
                                        <td class="text-end {{ $bagian['total'] < 0 ? 'text-error' : '' }}">{{ $rupiah($bagian['total']) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
