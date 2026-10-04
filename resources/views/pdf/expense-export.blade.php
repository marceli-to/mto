@php
  $fontPath = resource_path('sidecar-browsershot/fonts/');
  $fontLight = base64_encode(file_get_contents($fontPath . 'Poppins-Light.woff2'));
  $fontMedium = base64_encode(file_get_contents($fontPath . 'Poppins-Medium.woff2'));
  $money = fn ($amount) => number_format($amount ?? 0, 2, '.', "'");
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
<title>Ausgaben {{ $year }}</title>
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
  * {
    box-sizing: border-box;
  }
  body {
    font-family: 'Poppins', sans-serif;
    font-weight: 300;
    font-size: 10pt;
    line-height: 15pt;
    margin: 0;
  }
  strong, th {
    font-weight: 500;
  }
  h1 {
    font-size: 16pt;
    line-height: 20pt;
    font-weight: 500;
    color: #e94364;
    margin: 10mm 0 8mm;
  }
  h2 {
    font-size: 12pt;
    line-height: 16pt;
    font-weight: 500;
    margin: 0 0 3mm;
  }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  th {
    text-align: left;
    border-bottom: .1mm solid #000;
    padding: 0 2mm 2mm 0;
  }
  td {
    border-bottom: .1mm solid #000;
    vertical-align: top;
    padding: 1.5mm 2mm 1.5mm 0;
  }
  tr {
    break-inside: avoid;
  }
  .text-right {
    text-align: right;
    padding-right: 0;
  }
  .nowrap {
    white-space: nowrap;
  }
  .muted {
    color: #666;
  }
  .small {
    font-size: 8.5pt;
    line-height: 12pt;
  }
  .totals {
    width: 50%;
    margin-bottom: 10mm;
  }
  .totals tr:last-child td {
    border-bottom: .6mm solid #000;
  }

  .expense + .expense {
    break-before: page;
  }
  .expense-details {
    margin-top: 6mm;
    border-top: .1mm solid #000;
  }
  .receipt {
    margin-top: 6mm;
    width: 170mm;
    height: 180mm;
    display: flex;
    align-items: flex-start;
    justify-content: center;
  }
  .receipt img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
  }
</style>
</head>
<body>

@if ($overview ?? false)
  <h1>Ausgaben {{ $year }}</h1>

  <table class="totals">
    <thead>
      <tr>
        <th>Währung</th>
        <th class="text-right">Anzahl</th>
        <th class="text-right">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($totals as $currency => $total)
        <tr>
          <td>{{ $currency }}</td>
          <td class="text-right">{{ $total['count'] }}</td>
          <td class="text-right"><strong>{{ $money($total['amount']) }}</strong></td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table class="small">
    <thead>
      <tr>
        <th>Nr.</th>
        <th>Datum</th>
        <th>Titel</th>
        <th>Beschreibung</th>
        <th class="text-right">Betrag</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($expenses as $expense)
        <tr>
          <td class="nowrap">{{ $expense->number }}</td>
          <td class="nowrap">{{ $expense->dateFormated }}</td>
          <td>{{ $expense->title }}</td>
          <td>{{ $expense->description }}</td>
          <td class="text-right nowrap">{{ $expense->currency }} {{ $money($expense->amount) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
@endif

@foreach ($pages ?? [] as $page)
  @php($expense = $page['expense'])
  <div class="expense">
    <h1>Ausgabe {{ $expense->number }}</h1>
    <h2>{{ $expense->title }}</h2>

    <table class="expense-details">
      <tbody>
        <tr>
          <td>{{ $expense->description ? $expense->description . ', ' : '' }}{{ $expense->dateFormated }}</td>
          <td class="text-right" style="width: 25%;"><strong>{{ $expense->currency }} {{ $money($expense->amount) }}</strong></td>
        </tr>
      </tbody>
    </table>

    @if ($page['image'])
      <div class="receipt">
        <img src="{{ $page['image'] }}" alt="Beleg {{ $expense->number }}">
      </div>
    @elseif ($page['pdf'])
      <p class="muted">Beleg: siehe folgende Seite(n).</p>
    @else
      <p class="muted">Kein Beleg vorhanden.</p>
    @endif
  </div>
@endforeach

</body>
</html>
