<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Label - {{ $item->id }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
            display: flex;
            justify-content: center;
        }

        .label-container {
            width: 210mm;
            height: 148.5mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            border: 1px dashed #ccc;
            box-sizing: border-box;
        }

        #qr-code {
            width: 100mm;
            height: 100mm;
            margin-bottom: 30px;
        }

        .item-name {
            font-size: 32pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            padding: 0 20px;
        }

        .item-code {
            font-size: 16pt;
            color: #555;
            margin-top: 10px;
        }

        @media print {
            .no-print {
                display: none;
            }

            .label-container {
                border: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="label-container">
        <canvas id="qr-code"></canvas>

        <h1 class="item-name">
            {{ $item->detailBahan->nama ?? 'NAMA BARANG' }}
        </h1>

        <div class="item-code">
            KODE: {{ $item->kode }}
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script>
        (function() {
            var qr = new QRious({
                element: document.getElementById('qr-code'),
                value: JSON.stringify({
                    target_id: {{ $item->id }},
                    batch_key: "{{ $item->kode }}",
                    system: "INVENTORY"
                }),
                size: 500
            });
        })();
    </script>
</body>

</html>
