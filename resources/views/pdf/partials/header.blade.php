<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Invoice</title>
<style>
@font-face {
    font-family: 'AkrobatBold';
    src: url('{{ url("/") }}/assets/css/fonts/Akrobat-Bold.ttf') format("truetype");
    font-weight: normal;
    font-style: normal; 
}

@font-face {
    font-family: 'AkrobatRegular';
    src: url('{{ url("/") }}/assets/css/fonts/Akrobat-Regular.ttf') format("truetype");
    font-weight: normal;
    font-style: normal; 
}

@font-face {
    font-family: 'RobotoMono';
    src: url('{{ url("/") }}/assets/css/fonts/RobotoMono-Regular.ttf') format("truetype");
    font-weight: normal;
    font-style: normal; 
}


@page {
  size: A4;
  margin: 0;
}

@media print {
  html, body {
    width: 210mm;
    height: 297mm;
  }
}

html {
    margin: 0;
    padding: 0;
}

body {
    background-color: white;
    padding: 15mm 20mm 20mm 20mm;
}

* {
    font-family: 'AkrobatRegular', sans-serif !important;
    font-style: normal;
    font-stretch: normal;
    font-weight: normal;
    text-rendering: optimizeLegibility;
    color: #000000;
    margin: 0;
    padding: 0;
}

b, strong, .bold {
    font-family: 'AkrobatBold', sans-serif !important;
}

p {
    margin: 0;
}

table td {
    vertical-align: top;
}

.cf::after {
  content: "";
  clear: both;
  display: table;
}

header.page-header {
    position: fixed;
    top: 10mm;
    left: 0;
    text-align: center;
    width: 100%;
}

footer.page-footer {
    color: #000000;
    position: fixed;
    bottom: 10mm;
    position: fixed;
    text-align: center;
    width: 100%;
}

/* Meta data */
.mto-logo {
    display: inline-block;
    height: 20mm;
    position: fixed;
    top: 15mm;
    right: 20mm;
    width: 20mm;
    z-index: 100;
}

.mto-logo img {
    display: block;
    height: auto;
    width: 100%;
}

.mto-address {
    font-size: 10pt;
    line-height: 9pt;
}

.mto-code-start,
.mto-code-end {
    color: #000000;
    font-family: 'RobotoMono', sans-serif !important;
    font-size: 6pt;
    line-height: 1;
}

/* Generic elements */
h1 {
    font-family: 'AkrobatBold', sans-serif !important;
    font-size: 18pt;
    line-height: 1;
}

h2 {
    font-family: 'AkrobatBold', sans-serif !important;
    font-size: 16pt;
    line-height: 1;
}

/* Invoice data */
.invoice-address {
    font-size: 11pt;
    line-height: 1;
    margin-top: 20mm;
}

header.invoice-header {
    margin-top: 20mm;
}

header.invoice-header h1 {
    float: left;
    margin: -1mm 0 0 0;
    width: 65%;
}

header.invoice-header table {
    float: left;
    font-size: 10pt;
    line-height: 1;
    margin: 0;
    padding: 0;
    width: 35%;
}

header.invoice-header table td:nth-child(2n+2) {
    text-align: right;
}

main.invoice-body {
    font-size: 11pt;
    line-height: 15pt;
    margin-top: 5mm;
    text-align: left;
}

table.invoice-positions,
table.invoice-positions.is-journal {
    font-size: 11pt;
    line-height: 1;
    margin-top: 5mm;
    width: 100%;
}


table.invoice-positions.is-journal {
    margin-top: 10mm;
}

table.invoice-positions td,
table.invoice-positions th {
    padding: 0;
    vertical-align: middle;
}

table.invoice-positions thead {
    border-bottom: .1mm solid #000000;
    line-height: 1;
}

table.invoice-positions thead th,
table.invoice-positions tr.position td,
table.invoice-positions tr.position-footer td {
    padding: 1.5mm 0 1.75mm 0;
}

table.invoice-positions thead th {
    font-family: 'AkrobatBold', sans-serif !important;
}

table.invoice-positions tr.position td {
    border-bottom: .1mm solid #000000;
    vertical-align: top;
}

table.invoice-positions tr.position-footer td {
    border-bottom: .1mm solid #000000;
}

table.invoice-positions tr.position-footer--grandtotal td {
    font-family: 'AkrobatBold', sans-serif !important;
    border-bottom: .6mm solid #000000;
}

.invoice-remarks {
    line-height: 1; 
    margin-top: 20px;
}

.position-periode {
    width: 12%;
}

.position-cost {
    width: 17%;
}

.position-description {
    width: 56%;
}

.position-amount {
    width: 15%;
}

.align-right {
    text-align: right;
}

.invoice-journal {
    margin-top: 30mm;
    margin-bottom: 0mm;
}

.invoice-vat-info {
    font-size: 9pt;
    line-height: 1;
    text-align: right;
    margin-top: 1mm;
}

.payment-info-box {
    border: .3mm solid #000;
    bottom: -20mm;
    font-size: 10pt;
    line-height: 0.9;
    left: 20mm;
    padding: 1mm;
    position: absolute;
    width: 70mm;
}

.payment-status {
    background-color: #5cb85c;
    color: #fff;
    font-family: 'AkrobatBold', sans-serif !important;
    font-size: 12pt;
    line-height: 1.2;
    padding: 2mm;
    position: absolute;
    right: 20mm;
    top: 60mm;
}

.payment-info-box table {
    width: 100%;
}

.payment-info-box td {
    padding: 1mm;
}

ul {
    margin-left: 16px
}

li {
    display: list-item;
    list-style-type: circle;
    line-height: 10pt;
    margin-bottom: 1mm;
}

</style>
</head>
<body>
{{-- <script type="text/php">
if (isset($pdf)) {
    $font = $fontMetrics->getFont("basis-grotesque-regular-pro", "normal");
    $pdf->page_text(543, 810, "{PAGE_NUM}/{PAGE_COUNT}", $font, 9.5, array(0, 0, 0));
}
</script> --}}
<header class="page-header">
    <span class="mto-code-start">&lt;marceli.to&gt;</span>
</header>
<span class="mto-logo">
    <img src="{{ asset('assets/img/mto-logo.svg') }}" height="100" width="100">
</span>
<span class="mto-address"><strong>marceli.to</strong><br>Marcel Stadelmann<br>Letzigraben 149<br>8047 Zürich<br><br>m@marceli.to<br>078 749 74 09<br></span>

{{-- <div class="payment-info-box">
    <table>
        <tr>
            <td>Bank</td>
            <td>Credit Suisse AG<br>8400 Winterthur</td>
        </tr>
        <tr>
            <td>IBAN</td>
            <td>CH72 0483 5060 5364 1000 2</td>
        </tr>
        <tr>
            <td>Zugunsten</td>
            <td>Marcel Stadelmann<br>Letzigraben 149<br>8047 Zürich</td>
        </tr>
    </table>
</div> --}}

<footer class="page-footer">
    <span class="mto-code-end">&lt;/marceli.to&gt;</span>
</footer>