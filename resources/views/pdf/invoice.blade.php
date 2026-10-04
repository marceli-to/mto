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
  html, body {
    /* Without this the default body margin offsets the QR page and spills it
       onto a page of its own. */
    margin: 0;
    padding: 0;
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

  /* Page margins live here rather than in Browsershot, so the QR bill can
     opt out of them via a named page and reach the sheet's bottom edge. */
  @page {
    size: A4;
    margin: 10mm 20mm;
  }

  @page qrpage {
    size: A4;
    margin: 0;
  }

  /* thead/tfoot repeat on every printed page, which is what gives the logo
     and the address strip their per-page placement. */
  .page-layout {
    width: 100%;
    border-collapse: collapse;
  }

  .page-layout > thead > tr > td,
  .page-layout > tfoot > tr > td,
  .page-layout > tbody > tr > td {
    border: none;
    padding: 0;
  }

  /* Reserves the band the logo sits in, so content starts 30mm down. */
  .page-layout > thead > tr > td {
    height: 20mm;
    vertical-align: top;
  }

  /* Reserves the band the footer runs in, so content stops 30mm above the
     sheet's edge on every page. */
  .page-layout > tfoot > tr > td {
    height: 20mm;
  }

  .page-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 8pt;
    line-height: 1;
    font-weight: 300;
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

  .qr-page {
    page: qrpage;
    break-before: page;
    page-break-before: always;
    position: relative;
    width: 210mm;
    height: 297mm;
  }

  .qr-page .qr-bill-wrapper {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 210mm;
    /* Opaque, so the footer runner cannot print into the payment part. */
    background: #ffffff;
    /* Height comes from the payment part itself (~105mm). Pinning it to a
       round 105mm overflows by a fraction and breaks it onto another page. */
  }

</style>
</head>
<body class="font-light">

  <div class="page-footer">
    <span>{{ config('invoice.beneficiary_name') }}</span>+<span>{{ config('invoice.beneficiary_street') }} {{ config('invoice.beneficiary_building') }}</span>+<span>{{ config('invoice.beneficiary_zip') }} {{ config('invoice.beneficiary_city') }}</span>+<span>078 749 74 09</span>+<span>m@marceli.to</span>+<span>marceli.to</span>
  </div>

  <table class="page-layout">
    <thead>
      <tr><td>
        <header>
          <svg xmlns="http://www.w3.org/2000/svg" width="433.855" height="49.858" viewBox="0 0 433.855 49.858" class="logo">
                <path d="M43.438 5.46v33.779c0 2.399-1.979 4.379-4.38 4.379-2.399 0-4.319-1.979-4.319-4.379 0-15.72.06-21.779.42-24.959l-10.5 14.58c-.899 1.26-1.979 1.86-2.999 1.86-1.08 0-2.101-.601-3-1.86L8.28 14.28c.36 3.12.42 9.06.42 24.959 0 2.399-1.92 4.379-4.319 4.379-2.4 0-4.38-1.979-4.38-4.379V5.46C.001 2.58 2.341.18 5.28.18c1.62 0 3.18.72 4.2 2.1l12.239 17.04L33.958 2.28c1.021-1.38 2.58-2.1 4.2-2.1 2.94 0 5.28 2.4 5.28 5.28zM93.958 37.678c.18.54.3 1.08.3 1.62 0 2.58-2.46 4.319-4.74 4.319-1.859 0-3.359-1.08-4.02-3.119-.78-2.4-1.5-4.62-2.22-6.66h-15.84c-.659 2.1-1.439 4.32-2.22 6.779-.72 2.101-2.22 3-3.96 3-2.1 0-4.38-1.5-4.38-4.02 0-.539.061-1.08.301-1.68L69.119 4.68c.899-2.46 2.88-4.439 5.1-4.439h2.34c2.52 0 4.439 1.86 5.399 4.439l12 32.998zm-13.02-10.979c-4.32-13.319-5.4-17.159-5.7-20.159-.24 2.76-1.2 6.9-5.46 20.159h11.16zM137.337 37.018c.78.9 1.08 1.74 1.08 2.58 0 2.16-2.1 3.84-4.319 3.84-1.141 0-2.28-.48-3.181-1.439L116.038 26.04c.24 2.52.36 6.479.36 13.2 0 2.399-1.92 4.379-4.32 4.379-2.399 0-4.38-1.979-4.38-4.379V4.92c0-2.76 1.56-4.32 4.32-4.32h13.139c9.3 0 15.06 4.86 15.06 13.199 0 9.18-7.199 12.78-12.779 13.14 2.099 1.68 5.519 5.159 9.899 10.079zM116.398 8.459v12.3h8.159c4.439 0 6.96-2.46 6.96-6.24 0-3.66-2.521-6.06-6.96-6.06h-8.159zM174.538 43.739c-10.5 0-21.96-6.36-21.96-21.84 0-15.719 11.7-21.719 22.14-21.719 3.6 0 7.08.72 9.84 1.98 1.619.78 2.399 2.16 2.399 3.6 0 1.86-1.319 3.66-3.54 3.66a4.5 4.5 0 0 1-1.439-.24c-2.22-.72-4.62-1.08-6.96-1.08-7.14 0-13.739 3.72-13.739 13.799 0 9.9 6.6 13.859 13.859 13.859 2.28 0 4.56-.359 6.779-1.08.54-.18 1.021-.24 1.5-.24 2.16 0 3.42 1.74 3.42 3.601 0 1.38-.66 2.76-2.279 3.54-2.88 1.379-6.42 2.16-10.02 2.16zM205.438 43.198c-2.939 0-4.56-1.619-4.56-4.559V5.16c0-2.94 1.62-4.56 4.56-4.56h19.38c2.1 0 3.899 1.74 3.899 3.959 0 2.16-1.8 3.9-3.899 3.9h-15.239v9.42h11.999c2.1 0 3.84 1.74 3.84 3.84 0 2.1-1.74 3.84-3.84 3.84h-11.999v9.779h15.239c2.1 0 3.899 1.74 3.899 3.9 0 2.22-1.8 3.959-3.899 3.959h-19.38zM246.298 43.198c-2.76 0-4.319-1.56-4.319-4.319V4.56c0-2.399 1.979-4.379 4.38-4.379 2.399 0 4.319 1.98 4.319 4.379v30.779h14.279c2.16 0 3.96 1.74 3.96 3.9 0 2.22-1.859 3.959-3.96 3.959h-18.659zM281.997 4.56c0-2.88 2.22-4.319 4.38-4.319s4.32 1.439 4.32 4.319v34.679c0 2.879-2.16 4.319-4.32 4.319s-4.38-1.44-4.38-4.319V4.56zM314.938 39.239V8.459h-7.619c-2.16 0-3.96-1.74-3.96-3.9 0-2.22 1.8-3.959 3.96-3.959h23.938c2.101 0 3.9 1.74 3.9 3.959 0 2.16-1.8 3.9-3.9 3.9h-7.619v30.779c0 2.399-1.98 4.379-4.38 4.379-2.401.001-4.32-1.979-4.32-4.378zM343.138 21.899C343.138 9.24 352.378 0 364.917 0c12.6 0 21.779 9.3 21.779 21.899 0 12.6-9.18 21.899-21.779 21.899-12.539 0-21.779-9.239-21.779-21.899zm34.858 0c0-7.56-5.399-13.739-13.079-13.739s-13.079 6.18-13.079 13.739c0 7.561 5.399 13.74 13.079 13.74s13.079-6.18 13.079-13.74z"></path>
                <path fill="#E94264" d="M399.777 43.198h30.719c1.739 0 3.359 1.5 3.359 3.36 0 1.8-1.56 3.3-3.359 3.3h-30.719c-1.801 0-3.36-1.5-3.36-3.3 0-1.86 1.56-3.36 3.36-3.36z"></path>
              </svg>
        </header>
      </td></tr>
    </thead>

    <tfoot>
      <tr><td></td></tr>
    </tfoot>

    <tbody>
      <tr><td>

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
        <strong class="text-highlight">@if($invoice->is_reminder){{ $invoice->reminder_level }}. Mahnung @else Rechnung @endif</strong>
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
          <span>{{ config('invoice.vat_number') }}</span>
        </div>
        <div class="flex justify-between">
          <span class="font-medium">Zahlbar bis</span>
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
              {{ number_format($position->hours, 2, '.', "'") }} Std. à {{ number_format($position->rate, 2, '.', "'") }}@if($invoice->has_rate_increase_notice)<span>*</span>@endif
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
    <!-- /Invoice Positions -->

    <!-- Raised hourly rate information -->
    @if($invoice->has_rate_increase_notice)
    <div class="invoice-raised-hourly-rate font-size-xs" style="margin-top: 5mm;">
      *Der Stundenansatz wurde per 1. Januar 2026 aufgrund gestiegener Lebenshaltungs- und Betriebskosten angepasst.
    </div>
    @endif
    <!-- /Raised hourly rate information -->

    <!-- Payment Info -->
    <div class="payment-info">
      <table class="w-full keep-together">
        <tr>
          <td colspan="2" class="font-size-xs"><strong>Bankverbindung</strong></td>
        </tr>
        <tr>
          <td class="font-size-xs" style="width: 30%;">Bank</td>
          <td class="font-size-xs">{{ config('invoice.bank_name') }}</td>
        </tr>
        <tr>
          <td class="font-size-xs">IBAN</td>
          <td class="font-size-xs">{{ config('invoice.iban') }}</td>
        </tr>
        <tr>
          <td class="font-size-xs" style="border: none;">Zugunsten</td>
          <td class="font-size-xs">{{ config('invoice.beneficiary_name') }}<br>{{ config('invoice.beneficiary_street') }} {{ config('invoice.beneficiary_building') }}<br>{{ config('invoice.beneficiary_zip') }} {{ config('invoice.beneficiary_city') }}</td>
        </tr>
      </table>
    </div>

      </td></tr>
    </tbody>
  </table>

  @if(!empty($qrBill))
  <!-- Swiss QR bill — 210 x 105 mm, flush with the bottom of the last page -->
  <div class="qr-page">
    <div class="qr-bill-wrapper">
      {!! $qrBill !!}
    </div>
  </div>
  @endif

</body>
</html>
