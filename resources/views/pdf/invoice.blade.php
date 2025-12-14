@php
  $fontPath = resource_path('sidecar-browsershot/fonts/');
  $fontLight = base64_encode(file_get_contents($fontPath . 'Poppins-Light.woff2'));
  $fontMedium = base64_encode(file_get_contents($fontPath . 'Poppins-Medium.woff2'));
@endphp
<html lang="en">
<head>
<title>Invoice</title>
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
  
  .keep-together {
    break-inside: avoid;
  }

  .logo {
    display: block;
    height: auto !important;
    width: 50mm !important;
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

  .invoice-recipient {
    margin-top: 15mm;
  }

  .invoice-info {
    margin-top: 20mm;
  }

  .invoice-title {
    margin-top: 10mm;
  }

  .invoice-positions {
    margin-top: 10mm;
  }

  .payment-info {
    border: .3mm solid #000000;
    max-width: 80mm;
    margin-top: 10mm;
    padding: 1mm 2mm;
    break-inside: avoid;
    page-break-inside: avoid;
    display: inline-block;
    width: 80mm;
  }

  .payment-info table td {
    border: none; 
    padding: 1mm 0;
  }

</style>
</head>
<body class="font-light">

  <div>

    <!-- Invoice Recipient Address -->
    <div class="invoice-recipient font-size-sm">
      <strong>{{ $invoice->client->name }}</strong><br>
      @if ($invoice->client->byline){{ $invoice->client->byline }}<br>@endif
      @if ($invoice->client->street){{ $invoice->client->street }}<br>@endif
      {{ $invoice->client->zip }} {{ $invoice->client->city }}
    </div>

    <!-- Invoice Info -->
    <div class="invoice-info grid grid-cols-12">
      <h1 class="font-size-lg col-span-7">
        <strong class="text-highlight">Rechnung</strong>
      </h1>
      <div class="font-size-sm col-span-5">
        <div class="flex justify-between">
          <span>Nummer</span>
          <span>{{ $invoice->number }}</span>
        </div>
        <div class="flex justify-between">
          <span>Datum</span>
          <span>{{ \Carbon\Carbon::parse($invoice->date)->format('d.m.Y') }}</span>
        </div>
        <div class="flex justify-between">
          <span>MwSt-Nr.</span>
          <span>CHE-398.845.092 MWST</span>
        </div>
        <div class="flex justify-between">
          <span class="font-medium">Bezahlen bis:</span>
          <span class="font-medium">{{ \Carbon\Carbon::parse($invoice->date_due)->format('d.m.Y') }}</span>
        </div>
      </div>
    </div>

    <!-- Invoice Title -->
    <div class="invoice-title font-size-md">
      {{ $invoice->title }}
    </div>

    <!-- Invoice Positions -->
    <table class="invoice-positions font-size-sm w-full">
      <thead>
        <th>Periode</th>
        <th>Beschreibung</th>
        <th>Aufwand</th>
        <th style="text-align: right">Betrag</th>
      </thead>
      <tbody>
        @foreach($invoice->positions as $position)
        <tr>
          <td style="width: 15%">{{ $position->periode }}</td>
          <td style="width: 55%">{{ $position->description }}</td>
          <td style="width: 25%">
            @if($position->is_flat)
              Pauschal
            @elseif($position->is_fee)
              Spesen
            @else
              {{ number_format($position->hours, 2, '.', "'") }} Std. à {{ number_format($position->rate, 2, '.', "'") }}
            @endif
          </td>
          <td style="width: 5%; text-align: right">{{ number_format($position->amount, 2, '.', "'") }}</td>
        </tr>
        @endforeach
        <tr>
          <td colspan="3">Subtotal</td>
          <td style="text-align: right">{{ number_format($invoice->total, 2, '.', "'") }}</td>
        </tr>
        <tr>
          <td colspan="3">MwSt {{ $invoice->vat_rate }}%</td>
          <td style="text-align: right">{{ number_format($invoice->vat, 2, '.', "'") }}</td>
        </tr>
        <tr>
          <td colspan="3" style="border-bottom: 0.6mm solid #000000"><strong>Total</strong></td>
          <td style="text-align: right; border-bottom: 0.6mm solid #000000"><strong>{{ number_format($invoice->grandtotal, 2, '.', "'") }}</strong></td>
        </tr>
      </tbody>
    </table>

    <!-- Payment Info -->
    <div class="payment-info">
      <table class="w-full keep-together">
        <tr>
          <td colspan="2" class="font-size-xs"><strong>Bankverbindung</strong></td>
        </tr>
        <tr>
          <td class="font-size-xs" style="width: 30%;">Bank</td>
          <td class="font-size-xs">Raiffeisenbank Weinland</td>
        </tr>
        <tr>
          <td class="font-size-xs">IBAN</td>
          <td class="font-size-xs">CH22 8080 8003 1865 2284 6</td>
        </tr>
        <tr>
          <td class="font-size-xs" style="border: none;">Zugunsten</td>
          <td class="font-size-xs">Marcel Stadelmann<br>Letzigraben 149<br>8047 Zürich</td>
        </tr>
      </table>
    </div>

  </div>

</body>
</html>
