<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 0.5cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 4px 5px;
            vertical-align: top;
            line-height: 1.2;
        }

        .header-table {
            border: 1px solid #000;
            margin-bottom: 2px;
        }

        .header-table td {
            border: 1px solid #000;
        }

        .logo-box {
            width: 8%;
            text-align: center;
            vertical-align: middle;
        }

        .logo-box img {
            width: 30px;
            height: auto;
        }

        .title-box {
            width: 62%;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            vertical-align: middle;
        }

        .meta-box {
            width: 30%;
            padding: 0 !important;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            border: none;
            border-left: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 5px;
            font-size: 11px;
            height: 18px;
            vertical-align: middle;
        }

        .meta-table tr:last-child td {
            border-bottom: none;
        }

        .main-table {
            border: 1px solid #000;
        }

        .main-table td {
            border: 1px solid #000;
        }

        .col-no {
            width: 25px;
            text-align: center;
            font-weight: normal;
        }

        .col-label {
            width: 150px;
            font-weight: normal;
        }

        .col-value {
            font-weight: normal;
        }

        .bold-text {
            font-weight: bold;
        }

        .sig-table {
            width: 80%;
            margin-top: 25px;
            border-collapse: collapse;
        }

        .sig-table td {
            border: 1px solid #000;
            padding: 4px 8px;
        }

        .sig-no {
            width: 25px;
            border-top: none !important;
            border-bottom: none !important;
            border-left: none !important;
            border-right: 1px solid #000 !important;
        }

        .sig-label {
            width: 130px;
        }

        .sig-space {
            height: 30px;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td class="logo-box">
                <img src="{{ $logo_left }}" alt="Logo">
            </td>
            <td class="title-box">
                <b>Permintaan Incoming Inspection Ke QC</b>
            </td>
            <td class="meta-box" style="padding: 0;">
                <table class="meta-table">
                    <tr>
                        <td>No. Dokumen: {{ $no_dokumen }}</td>
                    </tr>
                    <tr>
                        <td>Revisi No.: {{ $revisi }}</td>
                    </tr>
                    <tr>
                        <td>Tanggal Berlaku: {{ $tgl_berlaku }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="height: 2px;"></div>

    <table class="main-table" style="border: 1px solid #000;">
        <tr>
            <td class="col-no">1.</td>
            <td class="col-label">Tanggal Permintaan:</td>
            <td class="col-value"></td>
        </tr>
        <tr>
            <td class="col-no">2.</td>
            <td class="col-label">Nama Barang:</td>
            <td class="col-value bold-text">{!! $nama_barang !!}</td>
        </tr>
        <tr>
            <td class="col-no">3.</td>
            <td class="col-label">Tanggal Barang Datang:</td>
            <td class="col-value">{{ $tanggal_permintaan }}</td>
        </tr>
        <tr>
            <td class="col-no">4.</td>
            <td class="col-label">Jumlah:</td>
            <td class="col-value">{{ number_format($total_palet, 0) }} PALET</td>
        </tr>
        <tr>
            <td class="col-no">5.</td>
            <td class="col-label">Nama <i>Supplier</i>:</td>
            <td class="col-value">{{ $supplier }}</td>
        </tr>
        <tr>
            <td class="col-no">6.</td>
            <td class="col-label">No. Surat Jalan:</td>
            <td class="col-value">{{ $lpb->no_sj }}</td>
        </tr>
        <tr>
            <td class="col-no">7.</td>
            <td class="col-label">Keterangan (Lokasi barang, dll):</td>
            <td class="col-value"></td>
        </tr>
    </table>

    <div class="footer-wrapper">
        <table class="sig-table">
            <tr>
                <td class="sig-no"></td>
                <td class="sig-label">Nama Admin Gudang:</td>
                <td class="col-value">{{ strtoupper($admin) }}</td>
            </tr>
            <tr>
                <td class="sig-no"></td>
                <td class="sig-label">Tanda Tangan:</td>
                <td class="sig-space"></td>
            </tr>
        </table>
    </div>
</body>

</html>
