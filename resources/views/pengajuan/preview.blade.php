<!DOCTYPE html>
<html>

<head>
    <title>Informasi Pengajuan Pembelian #{{ $pengajuan->id }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10pt;
            line-height: 1.4;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .header-title {
            font-size: 14pt;
            font-weight: bold;
        }

        .company-address {
            font-size: 9pt;
        }

        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }

        .doc-number {
            font-size: 11pt;
            font-weight: bold;
        }

        .content-table td,
        .content-table th {
            border: 1px solid black;
            padding: 8px 5px;
        }

        .content-table th {
            background-color: #f2f2f2;
            text-transform: uppercase;
            font-size: 9pt;
        }

        hr {
            border: 0;
            border-top: 1px solid #000;
            margin: 5px 0;
        }

        .signature-box {
            margin-top: 40px;
        }

        .footer-note {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: right;
            font-size: 8pt;
            color: #777;
        }
    </style>
</head>

<body>

    <table border="0" style="margin-bottom: 5px;">
        <tr>
            <td width="15%" align="left" style="vertical-align: middle;">
                <img src="{{ public_path('img/logomuliaoffset.png') }}" style="width: 70px; height: auto;">
            </td>
            <td width="70%" align="center" style="vertical-align: middle;">
                <div class="header-title">PT. MULIAOFFSET PACKINDO</div>
                <div class="company-address">
                    Madukoro Blok B No.15, Semarang 50144 Telp:(024)7603141-43<br>
                    Fax:(024)7603133 Homepage:www.muliaprintinggroup.com
                </div>
            </td>
            <td width="15%" align="right" style="vertical-align: middle;">
                <img src="{{ public_path('img/logofullnew_lokal.png') }}" style="width: 70px; height: auto;">
            </td>
        </tr>
    </table>
    <hr>

    <div class="text-center" style="margin-top: 15px;">
        <div class="doc-title">INFORMASI PROSES PENGAJUAN BARANG</div>
        <div class="doc-number">No. Ref: {{ $pengajuan->no_order }}</div>
    </div>
    <br>

    <table cellpadding="3" cellspacing="0" border="0" style="width: 100%;">
        <tr>
            <td width="15%" class="bold">Kepada</td>
            <td width="2%">:</td>
            <td width="40%">Bagian Purchasing</td>
            <td width="15%" class="bold">Tgl. Cetak</td>
            <td width="2%">:</td>
            <td width="26%">{{ date('d-m-Y') }}</td>
        </tr>
        <tr>
            <td class="bold">Dari</td>
            <td>:</td>
            <td></td>
            <td class="bold">Supplier</td>
            <td>:</td>
            <td>{{ $pengajuan->nama_suplier_asli }}</td>
        </tr>
        <tr>
            <td class="bold">Perihal</td>
            <td>:</td>
            <td>Laporan Barang Dalam Proses Pembelian</td>
            <td class="bold">Status</td>
            <td>:</td>
            <td class="bold">
                @if ($pengajuan->status == 0)
                    DIAJUKAN
                @elseif($pengajuan->status == 1)
                    DIPROSES OLEH PURCHASING
                @else
                    SELESAI
                @endif
            </td>
        </tr>
    </table>

    <div style="margin-top: 20px; margin-bottom: 10px; text-align: justify;">
        Berikut adalah rincian daftar barang yang telah diajukan oleh user dan saat ini sedang dalam tahap pengelolaan:
    </div>

    <table class="content-table" cellpadding="3">
        <thead>
            <tr class="text-center">
                <th width="5%">No</th>
                <th width="40%">Nama Barang / Deskripsi</th>
                <th width="10%">Qty</th>
                <th width="10%">Satuan</th>
                <th width="17%">Tgl Diminta</th>
                <th width="18%">Tgl Proses</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pengajuan->details as $index => $detail)
                <tr>
                    <td align="center">{{ $index + 1 }}</td>
                    <td>
                        {{ $detail->bahan ? $detail->bahan->nama : $detail->id_bahan }}
                    </td>
                    <td align="center">{{ number_format($detail->jumlah, 0, ',', '.') }}</td>
                    <td align="center">
                        {{ $detail->bahan ? $detail->bahan->satuan : $detail->satuan ?? '-' }}
                    </td>
                    <td align="center">{{ \Carbon\Carbon::parse($pengajuan->tanggal)->format('d-m-Y') }}</td>
                    <td align="center">
                        {{ $pengajuan->tanggal_diproses ? \Carbon\Carbon::parse($pengajuan->tanggal_diproses)->format('d-m-Y') : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 15px; border: 1px solid #ccc; padding: 10px;">
        <span class="bold">Catatan Pengaju:</span><br>
        {!! nl2br(e($pengajuan->notes ?? '-')) !!}
    </div>

    <div style="margin-top: 15px;">
        Demikian informasi pengajuan ini disampaikan sebagai bukti monitoring proses pengadaan barang.
    </div>

    <table class="signature-box" border="0">
        <tr>
            <td width="50%" align="center">
                <div>Pemohon,</div>
                <div style="height: 70px;"></div>
                <div class="bold" style="text-decoration: underline;">( ............................ )</div>
                <div style="font-size: 8pt;">User / Divisi Pengaju</div>
            </td>
            <td width="50%" align="center">
                <div>Mengetahui,</div>
                <div style="height: 70px;"></div>
                <div class="bold" style="text-decoration: underline;">( Admin Purchasing )</div>
                <div style="font-size: 8pt;">Bagian Pengadaan</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        <i>Dokumen ini diterbitkan secara otomatis oleh sistem inventory. Dicetak pada: {{ date('d-m-Y H:i:s') }}</i>
    </div>

</body>

</html>
