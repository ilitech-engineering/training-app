<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>@yield('title', 'ILITECH TRAINING')</title>
<meta name="description" content="@yield('desc')">
<meta property="og:title" content="@yield('title', 'ILITECH TRAINING')"><meta property="og:description" content="@yield('desc')"><meta property="og:image" content="{{ url('/img/logo.png') }}">
<link rel="icon" href="/img/favicon.png"><link rel="canonical" href="{{ url()->current() }}">
<meta name="theme-color" content="#04283D">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
<style>[hidden]{display:none!important}.lang a{border:1px solid #1f5878;color:#cfe;padding:4px 12px;border-radius:99px;font-size:.85rem;text-decoration:none;margin-inline-start:4px}.lang a.on{background:var(--teal);color:var(--ink);border-color:var(--teal);font-weight:700}.lang{display:flex;gap:4px}.err{color:#c0392b;margin-top:10px;font-size:.9rem}.lead a{color:var(--amber)}</style>
@stack('head')
</head>
<body>
<div class="bar"><div class="wrap">
  <a class="brand" href="{{ route('home') }}"><img src="/img/logo-light.png" alt="" width="34" height="34"><span>ILITECH <b>TRAINING</b></span></a>
  <nav class="lang" aria-label="Language">@foreach (['ar' => 'عربي', 'fr' => 'FR', 'en' => 'EN'] as $k => $v)<a href="{{ request()->fullUrlWithQuery(['lang' => $k]) }}" hreflang="{{ $k }}" @class(['on' => app()->getLocale() === $k])>{{ $v }}</a>@endforeach</nav>
</div></div>
<main>@yield('content')</main>
<footer><img src="/img/logo-light.png" alt="ILITECH TRAINING" width="48" height="48"><br>© {{ date('Y') }} ILITECH TRAINING · {{ __('ui.ft') }}</footer>
<script src="{{ asset('js/landing.js') }}" defer></script>
</body></html>
