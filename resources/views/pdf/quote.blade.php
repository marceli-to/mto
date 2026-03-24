@php
  $fontPath = resource_path('sidecar-browsershot/fonts/');
  $fontLight = base64_encode(file_get_contents($fontPath . 'Poppins-Light.woff2'));
  $fontMedium = base64_encode(file_get_contents($fontPath . 'Poppins-Medium.woff2'));
@endphp
<html lang="de">
<head>
<title>Angebot {{ $quote->title }}</title>
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

  .keep-together {
    break-inside: avoid;
  }

  .page-break {
    page-break-before: always;
  }

  /* Intro text styling for Tiptap HTML output */
  .intro-content h2 {
    font-weight: 500;
    font-size: 10pt;
    line-height: 15pt;
    margin-top: 6mm;
    margin-bottom: 1mm;
  }

  .intro-content h3 {
    font-weight: 500;
    font-size: 10pt;
    line-height: 15pt;
    margin-top: 6mm;
    margin-bottom: 1mm;
  }

  .intro-content p {
    font-size: 10pt;
    line-height: 15pt;
    margin-bottom: 2mm;
  }

  .intro-content ul {
    font-size: 10pt;
    line-height: 15pt;
    padding-left: 5mm;
    margin-bottom: 2mm;
  }

  .intro-content ul li {
    list-style-type: disc;
  }

  /* Section positions table */
  .section-table {
    width: 100%;
    border-collapse: collapse;
  }

  .section-table td {
    font-weight: 300;
    font-size: 10pt;
    line-height: 15pt;
    padding: 1.5mm 0;
    border-bottom: .1mm solid #000000;
  }

  .section-table .total-row td {
    border-bottom: .6mm solid #000000;
    font-weight: 500;
  }

  /* Terms page styling */
  .terms-content h3 {
    font-weight: 500;
    font-size: 10pt;
    line-height: 15pt;
    margin-top: 6mm;
    margin-bottom: 1mm;
  }

  .terms-content p {
    font-size: 10pt;
    line-height: 15pt;
    margin-bottom: 2mm;
  }
</style>
</head>
<body>

  {{-- ==================== PAGE 1: INTRO ==================== --}}
  <div>
    <!-- Recipient Address -->
    <div style="margin-top: 15mm;" class="font-size-sm">
      <strong>{{ $quote->client->name }}</strong><br>
      @if ($quote->client->byline){{ $quote->client->byline }}<br>@endif
      @if ($quote->client->street){{ $quote->client->street }}<br>@endif
      {{ $quote->client->zip }} {{ $quote->client->city }}
    </div>

    <!-- Date -->
    <div style="margin-top: 20mm;" class="font-size-sm">
      Zürich, {{ \Carbon\Carbon::parse($quote->date)->locale('de')->isoFormat('D. MMMM YYYY') }}
    </div>

    <!-- Title -->
    <div style="margin-top: 10mm;" class="font-size-lg">
      <strong class="text-highlight">Angebot</strong> {{ $quote->title }}
    </div>

    <!-- Greeting -->
    @if($quote->intro_greeting)
    <div style="margin-top: 8mm;" class="font-size-sm">
      {{ $quote->intro_greeting }}
    </div>
    @endif

    <!-- Intro Text (Tiptap HTML) -->
    @if($quote->intro_text)
    <div style="margin-top: 4mm;" class="intro-content">
      {!! $quote->intro_text !!}
    </div>
    @endif
  </div>

  {{-- ==================== PAGE 2+: POSITIONS ==================== --}}
  @if($quote->sections->count() > 0)
  <div class="page-break">
    <!-- Repeated Title -->
    <div class="font-size-lg" style="margin-bottom: 8mm;">
      <strong class="text-highlight">Angebot</strong> {{ $quote->title }}
    </div>

    @foreach($quote->sections as $section)
    <div class="keep-together" style="margin-bottom: 10mm;">
      <!-- Section Heading -->
      <div class="font-size-sm" style="margin-bottom: 3mm;">
        <strong>{{ $section->title }}</strong>
      </div>

      <!-- Section Positions -->
      <table class="section-table">
        <tbody>
          @foreach($section->positions as $position)
          <tr>
            <td>{{ $position->description }}</td>
            <td style="text-align: right; width: 20%;">{{ number_format($position->amount, 2, '.', "'") }}</td>
          </tr>
          @endforeach

          <!-- Section Total -->
          <tr class="total-row">
            <td><strong>{{ $section->total_label }}</strong></td>
            <td style="text-align: right;"><strong>{{ number_format($section->positions->sum('amount'), 2, '.', "'") }}</strong></td>
          </tr>
        </tbody>
      </table>

      <!-- VAT Note -->
      <div class="font-size-xs" style="text-align: right; margin-top: 2mm;">
        Beträge in CHF und exklusive {{ number_format($quote->vat_rate, 1) }}% MwSt.
      </div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- ==================== PAGE 3: TERMS (OPTIONAL) ==================== --}}
  @if($quote->include_terms_page)
  <div class="page-break terms-content">
    <!-- Repeated Title -->
    <div class="font-size-lg" style="margin-bottom: 8mm;">
      <strong class="text-highlight">Angebot</strong> {{ $quote->title }}
    </div>

    <h3>Budget</h3>
    <p>
      Das Budget basiert auf einer Schätzung des Arbeitsaufwandes. Sofern Änderungen im Projekt eintreten, welche das Budget beeinflussen, wird rechtzeitig auf diesen Umstand aufmerksam gemacht und die Offerte revidiert.
      @if($quote->daily_rate)<br>Tagesansatz: Fr. {{ number_format($quote->daily_rate, 2, '.', "'") }}@endif
      @if($quote->hourly_rate)<br>Stundenansatz: Fr. {{ number_format($quote->hourly_rate, 2, '.', "'") }}@endif
    </p>

    <h3>Upgrades</h3>
    <p>
      Die jährlichen Kosten für Upgrades belaufen sich auf Fr. 150.00. Diese beinhalten die Upgrade-Gebühren (Fr. 60.00) des CMS-Anbieters.
    </p>

    <h3>Technische Voraussetzungen Webhosting</h3>
    <p>
      Webserver: Linux/Unix-Umgebung mit Apache, Scriptsprache: PHP 8+, Datenbank: MySQL 5+, Composer und GIT
    </p>

    <h3>Konditionen</h3>
    <p>
      Diese Offerte ist 30 Tage gültig. Die Preise verstehen sich exklusiv Mehrwertsteuer.<br>
      Zahlungsmodus: Nach der Erbringung der in dieser Offerte beschriebenen Leistungen.<br>
      Nicht in dieser Offerte enthaltene Leistungen werden anhand einer speziellen Vereinbarung verrechnet.
    </p>

    <h3>Urheberrecht</h3>
    <p>
      Grundsätzlich gilt das Schweizerische Bundesgesetz über das Urheberrecht und verwandte Schutzrechte (Urheberrechtsgesetze, URG).
    </p>

    <h3>Zahlung</h3>
    <p>
      Bei Aufträgen im Wert von über Fr. 10'000.- gelten folgende Zahlungsbedingungen: 1/3 Anzahlung nach Erhalt der Auftragsbestätigung, 2/3 nach Fertigstellung des Projektes.
    </p>
  </div>
  @endif

</body>
</html>
