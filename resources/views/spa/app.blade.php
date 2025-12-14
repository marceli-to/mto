<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>marceli.to</title>
<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/favicon.svg" />
<link rel="shortcut icon" href="/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="marceli.to" />
@vite(['resources/css/app.css', 'resources/js/spa/app.js'])
<meta name="csrf-token" content="{{ csrf_token() }}" />
</head>
<body class="text-sm font-sans tracking-wide">
<div id="app"></div>
</body>
</html>