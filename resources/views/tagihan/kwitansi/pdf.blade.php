<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kwitansi {{ $receipt['number'] }}</title>
    <style>
        @page { size: A5 portrait; margin: 10mm 6mm 6mm; }
        html, body { background: #fff; margin: 0; padding: 0; }
        .pdf-receipt-page {
            box-sizing: border-box;
            margin: 0 auto;
            padding-top: 5mm;
            overflow: visible;
            width: 136mm;
        }
    </style>
</head>
<body>
    <div class="pdf-receipt-page">
        @include('tagihan.kwitansi._document', ['receipt' => $receipt])
    </div>
</body>
</html>
