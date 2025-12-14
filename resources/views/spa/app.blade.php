<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>marceli.to</title>
@vite(['resources/css/app.css', 'resources/js/spa/app.js'])
<meta name="csrf-token" content="{{ csrf_token() }}" />
</head>
<body class="text-sm font-mono">
<div id="app"></div>
</body>
</html>