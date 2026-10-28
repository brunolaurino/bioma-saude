<!doctype html>
<html lang="pt-BR"@if ($theme = \App\Support\Theme::attr()) data-theme="{{ $theme }}"@endif>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#0f3d25">
<meta name="robots" content="noindex, nofollow">
{{-- Navegação sem recarregar (Turbo). Sem cache de páginas: dados de saúde não ficam guardados para o botão Voltar. --}}
<meta name="turbo-cache-control" content="no-cache">
<meta name="view-transition" content="same-origin">
<title>{{ $title ?? 'Saúde App' }} · Saúde App</title>
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
<link rel="icon" href="/icons/icon-192.png" type="image/png">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
<link rel="stylesheet" href="/css/tokens.css?v=2" data-turbo-track="reload">
<link rel="stylesheet" href="/css/components.css?v=2" data-turbo-track="reload">
<link rel="stylesheet" href="/vendor/fontawesome/css/fontawesome.min.css">
<link rel="stylesheet" href="/vendor/fontawesome/css/solid.min.css">
<link rel="stylesheet" href="/vendor/fontawesome/css/regular.min.css">
<link rel="stylesheet" href="/css/app.css?v=2" data-turbo-track="reload">
<link rel="stylesheet" href="/css/theme.css?v=3" data-turbo-track="reload">
<script src="/vendor/turbo/turbo.js?v=8.0.12" defer data-turbo-track="reload"></script>
<script src="/js/app.js?v=4" defer data-turbo-track="reload"></script>
</head>
<body>
<a class="skip" href="#conteudo">Pular para o conteúdo</a>
@yield('body')
@include('partials.toast')
@include('partials.confirm')
</body>
</html>
