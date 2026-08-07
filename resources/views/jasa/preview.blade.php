<div class="p-3">
    <table style="width: 100%;">
        <tr>
            <td
                style="font-weight: bold; font-size: 0.9em; text-align: center; border-bottom: 2px double #000; padding-bottom: 5px;">
                INVOICE JASA: {{ $header->no_jasa }}
            </td>
        </tr>
    </table>

    <table style="width: 100%; margin-top: 15px;">
        <tr>
            <td align="left" style="font-weight: bold; font-size: 0.75em; width: 50%;">Kepada Yth,</td>
            <td align="right" style="font-weight: bold; font-size: 0.75em; width: 50%;">No Order: {{ $header->no_order }}
            </td>
        </tr>
        <tr>
            <td align="left" style="font-weight: bold; font-size: 0.85em; color: #1a73e8;">
                {{ strtoupper($header->nama) }}
            </td>
            <td align="right" style="font-weight: bold; font-size: 0.75em;">Term : {{ $header->term }}</td>
        </tr>
        <tr>
            <td colspan="2" align="left" style="font-weight: bold; font-size: 0.75em; padding-top: 5px;">
                UP : {{ strtoupper($header->untukperhatian) }}
            </td>
        </tr>
    </table>

    <div style="margin-top: 15px; font-weight: bold; font-size: 0.75em;">
        Dengan Hormat,<br>
        Bersama dengan ini kami sampaikan tagihan penyerahan Pekerjaan / Jasa dengan rincian nilai komparasi sebagai
        berikut:
    </div>

    <table style="width: 100%; margin-top: 10px; border-collapse: collapse; font-size: 0.8em;" cellpadding="6">
        <thead>
            <tr style="background-color: #f2f2f2; font-weight: bold; text-align: center;">
                <th style="border: 1px solid black; width: 10%;">No</th>
                <th style="border: 1px solid black; width: 65%;">Deskripsi Pekerjaan / Layanan Jasa</th>
                <th style="border: 1px solid black; width: 25%;">Jumlah Harga (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td align="center" style="border: 1px solid black; vertical-align: top;">1</td>
                <td align="left" style="border: 1px solid black;">
                    <b>Penyediaan Fasilitas Transaksi Jasa Terkait</b>
                    @if (!empty($header->notes))
                        <div style="margin-top: 5px; font-size: 0.9em; font-weight: normal; color: #555;">
                            {!! nl2br(e($header->notes)) !!}
                        </div>
                    @endif
                </td>
                <td align="right" style="border: 1px solid black; font-weight: bold; vertical-align: top;">
                    {{ number_format($header->totalexclude, 2, ',', '.') }}
                </td>
            </tr>

            <tr>
                <td colspan="2" align="right" style="border: 1px solid black; font-weight: bold;">Total Exclude:
                </td>
                <td align="right" style="border: 1px solid black; font-weight: bold;">
                    {{ number_format($header->totalexclude, 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td colspan="2" align="right" style="border: 1px solid black;">Diskon:</td>
                <td align="right" style="border: 1px solid black;">
                    {{ number_format($header->diskon, 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td colspan="2" align="right" style="border: 1px solid black;">PPN ({{ $header->ppn }}%):</td>
                <td align="right" style="border: 1px solid black;">
                    {{ number_format($header->totalppn, 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td colspan="2" align="right" style="border: 1px solid black;">
                    {{ $header->inputlabel == '-' ? 'Freight Handling' : $header->inputlabel }}:
                </td>
                <td align="right" style="border: 1px solid black;">
                    {{ number_format($header->ongkir, 2, ',', '.') }}
                </td>
            </tr>
            <tr style="background-color: #e8f0fe;">
                <td colspan="2" align="right" style="border: 1px solid black; font-weight: bold;">Grand Total
                    Pembelian:</td>
                <td align="right" style="border: 1px solid black; font-weight: bold; color: #1a73e8;">
                    {{ number_format($header->GrandTotalPembelian, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 25px; font-size: 0.8em;">
        <tr>
            <td style="width: 40%;"></td>
            <td style="width: 20%;"></td>
            <td align="center" style="font-weight: bold; width: 40%;">
                Semarang, {{ date('d F Y', strtotime($header->tanggal)) }}
            </td>
        </tr>
        <tr>
            <td align="center" style="font-weight: bold;">Pelanggan Konfirmasi</td>
            <td></td>
            <td align="center" style="font-weight: bold;">Hormat Kami,</td>
        </tr>
        <tr style="height: 60px;">
            <td colspan="3"></td>
        </tr>
        <tr>
            <td align="center" style="font-weight: bold; border-bottom: 1px solid #ddd;">
                {{ strtoupper($header->nama) }}
            </td>
            <td></td>
            <td align="center" style="font-weight: bold; border-bottom: 1px solid #ddd;">
                Roy Mulyono
            </td>
        </tr>
    </table>
</div>
