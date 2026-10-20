@extends('layout')
@section('title', __('ui.thx_ok'))
@section('content')
@php $c = $reg->course->contact ?? []; $ok = $reg->status === 'confirmed'; @endphp
<section><div class="wrap" style="max-width:560px"><div class="card" style="padding:26px">
<h2>{{ $ok ? '🎉 ' . __('ui.thx_ok') : '⏳ ' . __('ui.thx_wait') }}</h2>
<p class="lead">{{ $reg->course->name(app()->getLocale()) }} — {{ $reg->name }}</p>
@if ($ok)
<h3>{{ __('ui.sessions') }}</h3>
<ul class="tick">@foreach ($reg->session->meetings as $m)<li>{{ $m->n }}. {{ $m->starts_at->locale(app()->getLocale())->translatedFormat('l d F · H:i') }}@if ($m->topic) — {{ $m->topic }}@endif</li>@endforeach</ul>
@if ($reg->session->location)<p>📍 {{ __('ui.where') }}: {{ $reg->session->location }}</p>@endif
@if ($reg->session->meeting_url)<p>🔗 <a href="{{ $reg->session->meeting_url }}">{{ __('ui.link') }}</a></p>@endif
<p style="margin-top:14px"><a class="btn" href="{{ route('ics', $reg) }}">{{ __('ui.cal') }}</a></p>
@if (!empty($c['group']))<p style="margin-top:12px"><a href="{{ $c['group'] }}" target="_blank" rel="noopener">{{ __('ui.th3') }}</a></p>@endif
@else
<p><a href="{{ route('ccp', $reg) }}">{{ __('ui.m2') }}</a></p>
@endif
</div></div></section>
@endsection
