<html lang="en">
<head>
<title>Invoice</title>
@include('pdf.partials.reset')
<style>
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

  /* Page margins live here rather than in pdf.partials.page-frame, so the QR bill can
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
    white-space: nowrap;
    font-size: 8pt;
    line-height: 1;
    font-weight: 300;
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
    @include('pdf.partials.footer-address')
  </div>

  <table class="page-layout">
    <thead>
      <tr><td>
        <header>
          @include('pdf.partials.logo')
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
