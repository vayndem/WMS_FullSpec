<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #172033
        }

        h2 {
            margin: 0 0 4px
        }

        .meta {
            margin-bottom: 16px;
            color: #53627a
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th,
        td {
            border: 1px solid #ccd6e4;
            padding: 7px
        }

        th {
            background: #eaf2ff;
            text-align: left
        }

        .right {
            text-align: right
        }

        .minus {
            color: #b42318
        }

        .plus {
            color: #067647
        }
    </style>
</head>

<body>
    <h2>Stock Opname {{ $opname->number }}</h2>
    <div class="meta">Gudang: {{ $opname->warehouse->nama ?? '-' }} | Cut-off:
        {{ $opname->cutoff_at->format('d-m-Y H:i') }} | Status: {{ $opname->status }}</div>
    <table>
        <thead>
            <tr>
                <th>Barang</th>
                <th class="right">Sistem</th>
                <th class="right">Fisik</th>
                <th class="right">Selisih</th>
                @if ($financial)
                    <th class="right">Harga</th>
                    <th class="right">Nilai</th>
                @endif
                <th>Alasan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($opname->details as $detail)
                <tr>
                    <td>{{ $detail->bahan->nama ?? '-' }}</td>
                    <td class="right">{{ number_format($detail->system_quantity, 6, ',', '.') }}</td>
                    <td class="right">{{ number_format($detail->physical_quantity, 6, ',', '.') }}</td>
                    <td class="right {{ $detail->difference_quantity < 0 ? 'minus' : 'plus' }}">
                        {{ number_format($detail->difference_quantity, 6, ',', '.') }}</td>
                    @if ($financial)
                        <td class="right">Rp {{ number_format($detail->unit_cost, 2, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($detail->difference_value, 2, ',', '.') }}</td>
                    @endif
                    <td>{{ $detail->reason ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>Approval: {{ $opname->approved_by ? 'User #' . $opname->approved_by : '-' }} | Posting:
        {{ $opname->posted_by ? 'User #' . $opname->posted_by : '-' }}</p>
</body>

</html>
