<div class="p-3">
    <table style="width: 100%;">

        <tr>
            <td style="font-weight: bold; font-size: 0.8em; text-align: center;">{{ $header->no_po }}</td>
        </tr>
    </table>




    <table style="width: 100%; padding: 2cm>
        <tr>
            <td align="left"
        style="font-weight: bold; font-size: 0.70em; padding-top: 10px;">
        Kepada Yth,
        </td>
        <td align="right" style="font-weight: bold; font-size: 0.70em; padding-top: 10px;">
            {{ $header->no_order }}
        </td>
        </tr>
        <tr>
            <td align="left" style="font-weight: bold; font-size: 0.70em;">
                {{ strtoupper($header->nama) }}
            </td>
            <td align="right" style="font-weight: bold; font-size: 0.70em;">
                Term : {{ $header->term }}
            </td>
        </tr>
        <tr>
            <td colspan="2" align="left" style="font-weight: bold; font-size: 0.70em; padding-top: 10px;">
                UP : {{ strtoupper($header->untukperhatian) }}
            </td>
        </tr>
    </table>


    <table style="width: 100%; padding: 2cm">
        <tr>
            <td style="font-weight: bold; font-size: 0.70em; text-align: left;">Dengan Hormat</td>
        </tr>
        <tr>
            <td style="font-weight: bold; font-size: 0.70em; text-align: left;">Bersama dengan ini kami sampaikan
                purchase order dengan rincian sebagai berikut : : </td>
        </tr>
    </table>

    <table style="width: 100%;" cellpadding="3"
        style="font-size: 0.780em; table-layout: fixed; margin-left: 20px; margin-right: 20px; border-collapse: collapse; width: 100%;">
        <thead>
            <tr class="text-center">
                <td width="20px" align="center" style="border: 1px solid black;">No</td>
                <td width="250px "align="center" style="border: 1px solid black;">Nama</td>
                <td width="70px" align="center" style="border: 1px solid black;">QTY</td>
                <td width="70px "align="center" style="border: 1px solid black;">Satuan</td>
                <td width="70px "align="center" style="border: 1px solid black;">Rcv</td>
                <td width="75px" align="center" style="border: 1px solid black;">Harga</td>
                <td width="90px" align="center" style="border: 1px solid black;">Jumlah Harga</td>
            </tr>
        </thead>
        <tbody>
            @php $baris = 1;@endphp
            @php  $cetak = '';@endphp


            @foreach ($detailsx as $detail)
                <tr>
                    <td width="20px" align="center" style="border: 1px solid black;">
                        {{ $baris++ }}
                    </td>
                    <td width="250px" align="left" style="border: 1px solid black;">
                        {{ $detail->nama }}
                        @if (!empty($detail->keterangan_bahan))
                            <br>{!! nl2br(e($detail->keterangan_bahan)) !!}
                        @endif
                    </td>
                    <td width="70px" align="center" style="border: 1px solid black;">
                        {{ number_format($detail->jumlah, 2, ',', '.') }}
                    </td>
                    <td width="70px" align="left" style="border: 1px solid black;">
                        {{ $detail->satuan }}
                    </td>
                    <td width="70px" align="center" style="border: 1px solid black;">
                        {{ number_format($detail->diterima, 2, ',', '.') }}
                    </td>
                    <td width="75px" align="right" style="border: 1px solid black;">
                        {{ number_format($detail->harga, 2, ',', '.') }}
                    </td>
                    <td width="90px" align="right" style="border: 1px solid black;">
                        {{ number_format($detail->exclude, 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="6" align="right" style="border: 1px solid black;">Total :</td>
                <td width="90px" align="right" style="border: 1px solid black;">
                    {{ number_format($header->totalexclude, 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td width="35px">Note :</td>
                <td rowspan="4" colspan="4" width="305px" align="left">
                    {!! nl2br(e($header->notes)) !!}
                </td>
                <td width="145px" align="right" style="border: 1px solid black;">Diskon</td>
                <td width="90px" align="right" style="border: 1px solid black;">
                    {{ number_format($header->diskon, 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td width="35px"></td>
                <td width="145px" align="right" style="border: 1px solid black;">PPN</td>
                <td width="90px" align="right" style="border: 1px solid black;">
                    {{ number_format($header->totalppn, 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td width="35px"></td>
                <td width="145px" align="right" style="border: 1px solid black;">
                    {{ $header->inputlabel == '-' ? 'Freight Handling' : $header->inputlabel }}
                </td>
                <td width="90px" align="right" style="border: 1px solid black;">
                    {{ number_format($header->ongkir, 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td width="35px"></td>
                <td width="145px" align="right" style="border: 1px solid black;">Total Order</td>
                <td width="90px" align="right" style="border: 1px solid black;">
                    <b>{{ number_format($header->GrandTotalPembelian, 2, ',', '.') }}</b>
                </td>
            </tr>

        </tbody>
    </table><br>
    <table style="width: 100%; padding: 2cm">

        <tr>
            <td></td>
            <td></td>
            <td align="center" style="font-weight: bold; font-size: 0.8em;">Semarang
                ,{{ date('d F Y', strtotime($header->tanggal)) }} </td>
        </tr>
        <tr>
            <td align="center" style="font-weight: bold; font-size: 0.70em;">Supplier Confirmation</td>
            <td></td>
            <td align="center" style="font-weight: bold; font-size: 0.70em;">Best Regards</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td align="center"> </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td align="center"> </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td align="center"> </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td align="center"> </td>
        </tr>
        <tr>
            <td align="center" style="padding: 20px;font-weight: bold; font-size: 0.70em;">
                {{ $header->nama_supplier }}
            </td>
            <td></td>
            <td align="center" style="padding: 20px;font-weight: bold; font-size: 0.70em;"> Roy Mulyono </td>
        </tr>
    </table>

    @if ($historyRevisions->count() > 0)
        <div style="margin-top: 30px;">
            <h4 style="font-size: 0.9em; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 5px;">
                Riwayat Revisi PO
            </h4>

            <table style="width: 100%; font-size: 0.70em; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="border: 1px solid #000; padding: 5px;">No. Revisi</th>
                        <th style="border: 1px solid #000; padding: 5px;">Tanggal Revisi</th>
                        <th style="border: 1px solid #000; padding: 5px;">Total Exclude</th>
                        <th style="border: 1px solid #000; padding: 5px;">Total Diskon</th>
                        <th style="border: 1px solid #000; padding: 5px;">Total PPN</th>
                        <th style="border: 1px solid #000; padding: 5px;">Grand Total</th>
                        <th style="border: 1px solid #000; padding: 5px;">Cetak</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($historyRevisions as $history)
                        <tr>
                            <td style="border: 1px solid #000; padding: 5px; text-align: center;">
                                {{ $history->no_revisi }}</td>
                            <td style="border: 1px solid #000; padding: 5px; text-align: center;">
                                {{ date('d/m/Y H:i', strtotime($history->archived_at)) }}
                            </td>
                            <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                                Rp {{ number_format($history->totalexclude, 2, ',', '.') }}
                            </td>
                            <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                                Rp {{ number_format($history->diskon, 2, ',', '.') }}
                            </td>
                            <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                                Rp {{ number_format($history->totalppn, 2, ',', '.') }}
                            </td>
                            <td style="border: 1px solid #000; padding: 5px; text-align: right;">
                                Rp {{ number_format($history->GrandTotalPembelian, 2, ',', '.') }}
                            </td>
                            <td style="border: 1px solid #000; padding: 5px; text-align: center;">
                                <button class="btn-cetak-revisi"
                                    style="background-color: #4CAF50;
                                       color: white;
                                       border: none;
                                       padding: 5px 10px;
                                       cursor: pointer;"
                                    data-no-revisi="{{ $history->no_revisi }}">
                                    Cetak
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
<script>
    $(document).ready(function() {
        // Gunakan event delegation karena tombol ini dibuat secara dinamis
        $('#preview-content').on('click', '.btn-cetak-revisi', function() {
            const noRevisi = $(this).data('no-revisi');
            // Membuat URL ke rute baru kita
            const urlCetak = "{{ route('cetak.revisi') }}?no_revisi=" + encodeURIComponent(noRevisi);
            // Buka di tab baru
            window.open(urlCetak, '_blank');
        });
    });
</script>
