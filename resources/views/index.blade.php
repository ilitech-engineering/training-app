@extends('layout')
@section('title', 'ILITECH TRAINING')
@section('desc', __('ui.all_courses'))
@section('content')
<section><div class="wrap"><h1 style="font-size:2rem;margin-bottom:20px">{{ __('ui.all_courses') }}</h1>
<div class="grid">@foreach ($courses as $c)
  <a class="card" href="{{ route('course', $c->slug) }}" style="text-decoration:none"><h3>{{ $c->name($loc) }}</h3>
  @if ($n = $c->sessions->first())<p>📅 {{ $n->starts_at->locale($loc)->translatedFormat('d F Y') }} · {{ __('ui.' . $n->mode) }} · {{ $n->currentPrice() ? number_format($n->currentPrice(), 0, '', ' ') . ' ' . __('ui.da') : __('ui.free') }}</p>@endif
  <span class="val">{{ __('ui.see') }}</span></a>
@endforeach</div></div></section>
@endsection
