@php
  $fontPath = resource_path('sidecar-browsershot/fonts/');
  $fontLight = base64_encode(file_get_contents($fontPath . 'Poppins-Light.woff2'));
  $fontMedium = base64_encode(file_get_contents($fontPath . 'Poppins-Medium.woff2'));
@endphp
<html lang="en">
<head>
<title>Ausgabe</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  @font-face {
    font-family: 'Poppins';
    src: url('data:font/woff2;base64,{{ $fontLight }}') format('woff2');
    font-weight: 300;
    font-style: normal;
  }
  @font-face {
    font-family: 'Poppins';
    src: url('data:font/woff2;base64,{{ $fontMedium }}') format('woff2');
    font-weight: 500;
    font-style: normal;
  }
  strong {
    font-weight: 500;
  }
  body {
    font-family: 'Poppins', sans-serif;
  }
  
  th {
    border-bottom: .1mm solid #000000;
    text-align: left;
    font-weight: 500;
    padding-bottom: 2mm;
  }

  td {
    border-bottom: .1mm solid #000000;
    font-weight: 300;
    vertical-align: top;
    padding-bottom: 2mm;
    padding-top: 2mm;
  }

  tr {
    break-inside: avoid;
  }

  .font-size-xs {
    font-size: 9pt;
    line-height: 13pt;
  }
  
  .font-size-sm {
    font-size: 10pt;
    line-height: 15pt;
  }

  .font-size-md {
    font-size: 12pt;
    line-height: 16pt;
  }

  .font-size-lg {
    font-size: 16pt;
    line-height: 20pt;
  }

  .text-highlight {
    color: #e94364;
  }

  .expense-header {
    margin-bottom: 10mm;
    margin-top: 10mm;
  }

  .expense-positions {
    margin-top: 5mm;
    border-top: .1mm solid #000000;
  }

  .page-break {
    page-break-after: always;
  }

  .receipt-container {
    margin-top: 35mm;
    max-height: 220mm;
    max-width: 160mm;
    overflow: hidden;
    margin-left: auto;
    margin-right: auto;
  }

  .receipt-container img {
    width: auto;
    max-width: 100%;
    height: auto;
    display: block;
  }

</style>
</head>
<body class="font-light">

<div>

  <!-- Expense Header -->
  <div class="expense-header">
    <h1 class="font-size-lg">
      <strong class="text-highlight">Ausgabe</strong>
    </h1>
  </div>

  <!-- Expense Title -->
  <div class="font-size-md">
    <strong>{{ $expense->title }}</strong>
  </div>

  <!-- Expense Details -->
  <table class="expense-positions font-size-sm w-full">
    <tbody>
      <tr>
        <td>{{ $expense->description }}, {{ \Carbon\Carbon::parse($expense->date)->format('d.m.Y') }}</td>
        <td style="text-align: right; width: 25%;">{{ number_format($expense->amount, 2, '.', "'") }}</td>
      </tr>
      <tr>
        <td style="border-bottom: 0.6mm solid #000000;"><strong>Total {{ $expense->currency }}</strong></td>
        <td style="text-align: right; border-bottom: 0.6mm solid #000000;"><strong>{{ number_format($expense->amount, 2, '.', "'") }}</strong></td>
      </tr>
    </tbody>
  </table>

</div>

<!-- Page Break -->
<div class="page-break"></div>

<!-- Receipt Image -->
@php
  $receiptPath = storage_path('app/public/media/expenses/' . $expense->number . '.jpg');
  $receiptBase64 = file_exists($receiptPath) ? base64_encode(file_get_contents($receiptPath)) : null;
@endphp
@if($receiptBase64)
<div class="receipt-container">
  <img src="data:image/jpeg;base64,{{ $receiptBase64 }}" alt="Receipt">
</div>
@endif

</body>
</html>
