<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kwitansi {{ $receipt['number'] }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        html, body { background: #fff; margin: 0; padding: 0; }
        .pdf-receipt-page { margin: 0 auto; overflow: hidden; width: 186mm; }
    </style>
</head>
<body>
    <div class="pdf-receipt-page">
        @include('tagihan.kwitansi._document', ['receipt' => $receipt])
    </div>
</body>
</html>
