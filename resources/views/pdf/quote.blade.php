@php
  $fontPath = resource_path('sidecar-browsershot/fonts/');
  $fontLight = base64_encode(file_get_contents($fontPath . 'Poppins-Light.woff2'));
  $fontMedium = base64_encode(file_get_contents($fontPath . 'Poppins-Medium.woff2'));
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
<title>{{ $quote->label }} {{ $quote->title }}</title>
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
    margin: 0;
    padding: 0;
  }
  strong, b {
    font-weight: 500;
  }
  body {
    font-family: 'Poppins', sans-serif;
    font-weight: 300;
    font-size: 10pt;
    line-height: 15pt;
  }

  .font-size-xs {
    font-size: 8pt;
    line-height: 12pt;
  }

  .font-size-lg {
    font-size: 13pt;
    line-height: 18pt;
  }

  .text-highlight {
    color: #e94364;
  }

  .doc-title strong:not(.text-highlight) {
    font-weight: 300;
  }

  /*
   * Every page after the letter is a table whose header holds the title:
   * Chromium repeats a <thead> on each printed page, so the title also
   * appears on pages that long text spills onto.
   */
  .page {
    width: 100%;
    border-collapse: collapse;
    break-before: page;
  }

  .page > thead > tr > td {
    padding-bottom: 5.75mm;
  }

  .page > tbody > tr > td {
    padding: 0;
    vertical-align: top;
  }

  /* Tiptap HTML */
  .rich > * + * {
    margin-top: 4.5mm;
  }

  .rich.letter > * + * {
    margin-top: 3mm;
  }

  .rich h2,
  .rich h3 {
    font-size: 10pt;
    line-height: 15pt;
    font-weight: 500;
    break-after: avoid;
  }

  .rich > h2:not(:first-child) {
    margin-top: 8mm;
  }

  .rich > h2 + *,
  .rich > h3 + * {
    margin-top: 0;
  }

  .rich h2 + h3 {
    margin-top: 4.5mm !important;
  }

  .rich ul,
  .rich ol {
    list-style: none;
  }

  .rich li {
    position: relative;
    padding-left: 4mm;
  }

  .rich li + li {
    margin-top: 1mm;
  }

  .rich ul > li::before {
    content: '–';
    position: absolute;
    left: 0;
  }

  .rich ol {
    counter-reset: item;
  }

  .rich ol > li {
    counter-increment: item;
    padding-left: 5mm;
  }

  .rich ol > li::before {
    content: counter(item) '.';
    position: absolute;
    left: 0;
  }

  .rich a {
    color: inherit;
  }

  /* Prices */
  .rich + .prices {
    margin-top: 8mm;
  }

  .section {
    break-inside: avoid;
  }

  .section + .section {
    margin-top: 8mm;
  }

  .section.has-note + .section {
    margin-top: 16mm;
  }

  .section-table {
    width: 100%;
    border-collapse: collapse;
  }

  .section-table td {
    padding: 3mm 0 1mm;
    border-bottom: .1mm solid #000000;
    vertical-align: bottom;
  }

  .section-table td.amount {
    text-align: right;
    white-space: nowrap;
    padding-left: 8mm;
  }

  .section-table .spacer td {
    padding: 0;
    height: 7mm;
  }

  .section-table .total-row td {
    font-weight: 500;
    padding-top: 3.5mm;
  }

  .vat-note {
    text-align: right;
    margin-top: 2mm;
  }
</style>
</head>
<body>

  {{-- ==================== PAGE 1: LETTER ==================== --}}
  <div>
    <div>
      <strong>{{ $quote->client->name }}</strong><br>
      @if ($quote->client->byline){{ $quote->client->byline }}<br>@endif
      @if ($quote->client->street){{ $quote->client->street }}<br>@endif
      {{ $quote->client->zip }} {{ $quote->client->city }}
    </div>

    <div style="margin-top: 20mm;">
      Zürich, {{ \Carbon\Carbon::parse($quote->date)->locale('de')->isoFormat('D. MMMM YYYY') }}
    </div>

    <div style="margin-top: 15mm;" class="font-size-lg doc-title">
      <strong class="text-highlight">{{ $quote->label }}</strong> {{ $quote->title }}
    </div>

    @if($quote->intro_greeting)
    <div style="margin-top: 6mm;">
      {{ $quote->intro_greeting }}
    </div>
    @endif

    @if($quote->intro_text)
    <div style="margin-top: 6mm;" class="rich letter">
      {!! $quote->intro_text !!}
    </div>
    @endif
  </div>

  {{-- ==================== PAGES ==================== --}}
  @foreach($quote->pages as $page)
  <table class="page">
    <thead>
      <tr>
        <td class="font-size-lg doc-title">
          <strong class="text-highlight">{{ $quote->label }}</strong> {{ $quote->title }}
        </td>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          @if($page->content && strip_tags($page->content) !== '')
          <div class="rich">
            {!! $page->content !!}
          </div>
          @endif

          @if($page->type === \App\Models\QuotePage::PRICES && $quote->sections->count() > 0)
          <div class="prices">
            @foreach($quote->sections as $section)
            <div @class(['section', 'has-note' => $section->show_vat_note])>
              @if($section->title)
              <div><strong>{{ $section->title }}</strong></div>
              @endif

              <table class="section-table">
                <tbody>
                  @foreach($section->positions as $position)
                  <tr>
                    <td>{{ $position->description }}</td>
                    <td class="amount">{{ $position->amount_text ?: $section->formatAmount($position->amount) }}</td>
                  </tr>
                  @endforeach

                  @if($section->show_total)
                  <tr class="spacer"><td colspan="2"></td></tr>
                  <tr class="total-row">
                    <td>{{ $section->total_label }}</td>
                    <td class="amount">{{ $section->formatAmount($section->total) }}</td>
                  </tr>
                  @endif
                </tbody>
              </table>

              @if($section->show_vat_note)
              <div class="vat-note font-size-xs">
                Beträge in {{ $section->currency ?: 'CHF' }}@if($quote->vat_rate > 0) und exklusive {{ number_format($quote->vat_rate, 1) }}% MwSt.@else.@endif
              </div>
              @endif
            </div>
            @endforeach
          </div>
          @endif
        </td>
      </tr>
    </tbody>
  </table>
  @endforeach

</body>
</html>
