@php
    $fontPath = resource_path('sidecar-browsershot/fonts/');
    $muotoRegular = base64_encode(file_get_contents($fontPath . 'Muoto-Regular.woff2'));
    $muotoLight = base64_encode(file_get_contents($fontPath . 'Muoto-Light.woff2'));
    $muotoLightItalic = base64_encode(file_get_contents($fontPath . 'Muoto-Light-Italic.woff2'));
@endphp
<html lang="en">
<head>
    <title>Invoice</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @font-face {
            font-family: 'Muoto';
            src: url('data:font/woff2;base64,{{ $muotoRegular }}') format('woff2');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Muoto';
            src: url('data:font/woff2;base64,{{ $muotoLight }}') format('woff2');
            font-weight: 300;
            font-style: normal;
        }
        @font-face {
            font-family: 'Muoto';
            src: url('data:font/woff2;base64,{{ $muotoLightItalic }}') format('woff2');
            font-weight: 300;
            font-style: italic;
        }
        @page {
            margin: 2rem;
        }
        body {
            font-family: 'Muoto', sans-serif;
        }
        tr {
            break-inside: avoid;
        }
        .keep-together {
            break-inside: avoid;
        }
        .logo {
            display: block;
            height: 40px !important;
            width: auto !important;
        }
    </style>
</head>
<body>

<div class="max-w-xl mx-auto font-light">
    
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center">
            <svg width="70" height="80" viewBox="0 0 70 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-auto h-12">
              <path d="M0 40.41L35 20.21L49.97 28.85L15.83 48.56V80H0V40.41Z" fill="currentColor"></path>
              <path d="M54.17 0H70V39.59L35 59.79L20.03 51.15L54.17 31.44V0Z" fill="currentColor"></path>
              <path d="M70 80H54.17V53.57L70 44.43V80Z" fill="currentColor"></path>
              <path d="M15.83 0V26.43L0 35.57V0H15.83Z" fill="currentColor"></path>
            </svg>
        </div>
        <div class="text-gray-700">
            <div class="font-regular text-xl mb-2 uppercase">Invoice</div>
            <div class="font-regular text-sm">Date: 01/05/2023</div>
            <div class="font-regular text-sm">Invoice #: {{ $invoiceNumber }}</div>
        </div>
    </div>
    <div class="border-b-2 border-gray-300 pb-8 mb-8">
        <h2 class="text-2xl mb-4">Bill To:</h2>
        <div class="text-gray-700 mb-2">{{ $customerName }}</div>
        <div class="text-gray-700 mb-2">123 Main St.</div>
        <div class="text-gray-700 mb-2">Anytown, USA 12345</div>
        <div class="text-gray-700">johndoe@example.com</div>
    </div>
    <table class="w-full text-left mb-8">
        <thead>
        <tr>
            <th class="text-gray-700 uppercase py-2">Description</th>
            <th class="text-gray-700 uppercase py-2">Quantity</th>
            <th class="text-gray-700 uppercase py-2">Price</th>
            <th class="text-gray-700 uppercase py-2">Total</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="py-4 text-gray-700">Product 1</td>
            <td class="py-4 text-gray-700">1</td>
            <td class="py-4 text-gray-700">$100.00</td>
            <td class="py-4 text-gray-700">$100.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 2</td>
            <td class="py-4 text-gray-700">2</td>
            <td class="py-4 text-gray-700">$50.00</td>
            <td class="py-4 text-gray-700">$100.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 3</td>
            <td class="py-4 text-gray-700">3</td>
            <td class="py-4 text-gray-700">$75.00</td>
            <td class="py-4 text-gray-700">$225.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 4</td>
            <td class="py-4 text-gray-700">1</td>
            <td class="py-4 text-gray-700">$120.00</td>
            <td class="py-4 text-gray-700">$120.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 5</td>
            <td class="py-4 text-gray-700">2</td>
            <td class="py-4 text-gray-700">$45.00</td>
            <td class="py-4 text-gray-700">$90.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 6</td>
            <td class="py-4 text-gray-700">1</td>
            <td class="py-4 text-gray-700">$200.00</td>
            <td class="py-4 text-gray-700">$200.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 7</td>
            <td class="py-4 text-gray-700">4</td>
            <td class="py-4 text-gray-700">$25.00</td>
            <td class="py-4 text-gray-700">$100.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 8</td>
            <td class="py-4 text-gray-700">1</td>
            <td class="py-4 text-gray-700">$350.00</td>
            <td class="py-4 text-gray-700">$350.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 9</td>
            <td class="py-4 text-gray-700">2</td>
            <td class="py-4 text-gray-700">$80.00</td>
            <td class="py-4 text-gray-700">$160.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 10</td>
            <td class="py-4 text-gray-700">1</td>
            <td class="py-4 text-gray-700">$95.00</td>
            <td class="py-4 text-gray-700">$95.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 11</td>
            <td class="py-4 text-gray-700">3</td>
            <td class="py-4 text-gray-700">$60.00</td>
            <td class="py-4 text-gray-700">$180.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 12</td>
            <td class="py-4 text-gray-700">1</td>
            <td class="py-4 text-gray-700">$175.00</td>
            <td class="py-4 text-gray-700">$175.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 13</td>
            <td class="py-4 text-gray-700">2</td>
            <td class="py-4 text-gray-700">$55.00</td>
            <td class="py-4 text-gray-700">$110.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 14</td>
            <td class="py-4 text-gray-700">1</td>
            <td class="py-4 text-gray-700">$225.00</td>
            <td class="py-4 text-gray-700">$225.00</td>
        </tr>
        <tr>
            <td class="py-4 text-gray-700">Product 15</td>
            <td class="py-4 text-gray-700">2</td>
            <td class="py-4 text-gray-700">$40.00</td>
            <td class="py-4 text-gray-700">$80.00</td>
        </tr>
        </tbody>
    </table>
    <div class="keep-together">
        <div class="flex justify-end mb-8">
            <div class="text-gray-700 mr-2">Subtotal:</div>
            <div class="text-gray-700">$425.00</div>
        </div>
        <div class="text-right mb-8">
            <div class="text-gray-700 mr-2">Tax:</div>
            <div class="text-gray-700">$25.50</div>
        </div>
        <div class="flex justify-end mb-8">
            <div class="text-gray-700 mr-2">Total:</div>
            <div class="text-gray-700 text-xl">$450.50</div>
        </div>
    </div>

    <div class="keep-together border-t-2 border-gray-300 pt-8 mb-8 mt-8">
        <div class="text-gray-700 mb-2">Payment is due within 30 days. Late payments are subject to fees.</div>
        <div class="text-gray-700 mb-2">Please make checks payable to Your Company Name and mail to:</div>
        <div class="text-gray-700">123 Main St., Anytown, USA 12345</div>
    </div>
</div>

</body>
</html>
