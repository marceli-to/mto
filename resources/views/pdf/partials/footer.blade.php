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
  .dot {
    font-size: 3pt;
    line-height: 1;
  }
  footer {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 5mm;
  }
</style>
</head>
<body>
  <footer>
    Marcel Stadelmann <span class="dot">&bull;</span> Letzigraben 149 <span class="dot">&bull;</span> 8047 Zürich <span class="dot">&bull;</span> 078 749 74 09 <span class="dot">&bull;</span> m@marceli.to
  </footer>
</body>
</html>
