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
    margin-bottom: 5mm;
    white-space: nowrap;
  }
</style>
</head>
<body>
  <footer>
    {!! collect([
      config('invoice.beneficiary_name'),
      config('invoice.beneficiary_street') . ' ' . config('invoice.beneficiary_building'),
      config('invoice.beneficiary_zip') . ' ' . config('invoice.beneficiary_city'),
      '078 749 74 09',
      'm@marceli.to',
    ])->map(fn ($item) => e($item))->implode('&thinsp;•&thinsp;') !!}
    @if ($pageNumbers ?? false)<span class="pageNumber" style="position: absolute; right: 20mm;"></span>@endif
  </footer>
</body>
</html>
