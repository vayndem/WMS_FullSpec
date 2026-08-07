{{-- File: resources/views/purchasing/previewhistory.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $pdfTitle }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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

        .font-bold {
            font-weight: bold;
        }

        .bordered-table th,
        .bordered-table td {
            border: 1px solid black;
            padding: 5px;
        }

        .revision-header {
            background-color: #ffebeb;
            border: 1px dashed #c00;
            padding: 8px;
            margin-bottom: 10px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="revision-header">
        DOKUMEN ARSIP REVISI<br>
        No. Revisi: {{ $header->no_revisi }} | Tanggal Arsip:
        {{ date('d F Y, H:i:s', strtotime($header->archived_at)) }}
    </div>

    {{-- Sisa dokumen (data supplier, detail barang, dll) diambil dari variabel $header dan $detailsx --}}
    <table style="margin-top: 15px; font-size: 0.8em; font-weight: bold;" cellpadding="2">
        <tr>
            <td style="width: 60%;">Kepada Yth,</td>
            <td style="width: 40%; text-align: right;">{{ $header->no_order != '-' ? $header->no_order : '' }}</td>
        </tr>
        <tr>
            <td>{{ strtoupper($header->supplier->nama) }}
            </td>
            <td style="text-align: right;">Term : {{ $header->term }}</td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top: 10px;">UP :
                {{ strtoupper($header->untukperhatian == '-' ? $header->contact_person : $header->untukperhatian) }}
            </td>
        </tr>
    </table>

    {{-- TABEL DETAIL BARANG --}}
    <table class="bordered-table" style="margin-top: 10px; font-size: 0.78em;">
        <thead>
            <tr style="text-align: center; font-weight: bold; background-color: #f2f2f2;">
                <th width="35">No</th>
                <th width="270">Nama Barang</th>
                <th width="60">QTY</th>
                <th width="80">Harga Satuan</th>
                <th width="100">Jumlah Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($header->details as $index => $detail)
                <tr>
                    <td width="35" style="text-align: center;">{{ $index + 1 }}</td>
                    <td width="270">
                        {{ $detail->bahan->nama ?? 'N/A' }}
                        @if (!empty($detail->bahan->keterangan_bahan))
                            <br><small><i>{{ $detail->bahan->keterangan_bahan }}</i></small>
                        @endif
                    </td>
                    <td width="60" style="text-align: right;">{{ number_format($detail->jumlah, 0, ',', '.') }}
                    </td>
                    <td width="80" style="text-align: right;">{{ number_format($detail->harga, 2, ',', '.') }}</td>
                    <td width="100" style="text-align: right;">{{ number_format($detail->exclude, 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            {{-- Baris Total Detail --}}
            <tr style="font-weight: bold;">
                <td colspan="4" width="445" style="text-align: right; border-top: 2px solid black;">Total Harga
                </td>
                <td width="100" style="text-align: right; border-top: 2px solid black;">
                    {{ number_format($header->totalexclude, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TABEL NOTE DAN GRAND TOTAL --}}
    <table style="margin-top: 1px; width: 100%;">
        <tbody>
            <tr>
                {{-- Kolom Kiri untuk Note, lebarnya sama dengan 3 kolom pertama di atas --}}
                <td style="width: 350px; vertical-align: top; font-size: 0.8em; padding: 5px;">
                    <strong>Note :</strong><br>
                    {!! nl2br(e($header->notes)) !!}
                </td>

                {{-- Kolom Kanan untuk Rincian Total, lebarnya sama dengan 2 kolom terakhir di atas --}}
                <td style="width: 198px; vertical-align: top;">
                    {{-- Tabel Bersarang di dalam Kolom Kanan --}}
                    <table style="width: 100%; font-size: 0.78em;">
                        <tr>
                            <td style="width: 46%; text-align: right; border: 1px solid black; padding: 4px;">Diskon
                            </td>
                            <td style="width: 54%; text-align: right; border: 1px solid black; padding: 4px;">
                                {{ number_format($header->diskon, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: right; border: 1px solid black; padding: 4px;">PPN</td>
                            <td style="text-align: right; border: 1px solid black; padding: 4px;">
                                {{ number_format($header->totalppn, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: right; border: 1px solid black; padding: 4px;">
                                {{ $header->inputlabel == '-' ? 'Freight Handling' : $header->inputlabel }}</td>
                            <td style="text-align: right; border: 1px solid black; padding: 4px;">
                                {{ number_format($header->ongkir, 2, ',', '.') }}</td>
                        </tr>
                        <tr style="font-weight: bold;">
                            <td style="text-align: right; border: 1px solid black; padding: 4px;">Total Order</td>
                            <td style="text-align: right; border: 1px solid black; padding: 4px;">
                                {{ number_format($header->GrandTotalPembelian, 2, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>


</body>

</html>
