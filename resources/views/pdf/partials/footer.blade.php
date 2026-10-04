@php
  $fontPath = resource_path('sidecar-browsershot/fonts/');
  $fontLight = base64_encode(file_get_contents($fontPath . 'Poppins-Light.woff2'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<style>
  @font-face {
    font-family: 'Poppins';
    src: url('data:font/woff2;base64,{{ $fontLight }}') format('woff2');
    font-weight: 300;
    font-style: normal;
  }
  body {
    font-family: 'Poppins', sans-serif;
    font-weight: 300;
    font-size: 8pt;
    line-height: 1;
    margin: 0;
    padding: 0 20mm;
  }

  footer {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 5mm;
  }
</style>
</head>
<body>
  <footer>
    <span>{{ config('invoice.beneficiary_name') }}</span>+<span>{{ config('invoice.beneficiary_street') }} {{ config('invoice.beneficiary_building') }}</span>+<span>{{ config('invoice.beneficiary_zip') }} {{ config('invoice.beneficiary_city') }}</span>+<span>078 749 74 09</span>+<span>m@marceli.to</span>+<span>marceli.to</span>
  </footer>
</body>
</html>
