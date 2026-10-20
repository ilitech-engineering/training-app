@extends('layout')
@section('title', __('ui.m2'))
@section('content')
@php $c = $reg->course->contact ?? []; $wa = 'https://wa.me/' . ($c['whatsapp'] ?? '') . '?text=' . urlencode("Reçu de paiement — {$reg->course->name('fr')} — {$reg->name} — " . number_format($reg->amount, 0, '', ' ') . ' DA'); @endphp
<section><div class="wrap" style="max-width:480px"><div class="card" style="padding:26px">
<div class="sum"><h3>{{ __('ui.z1') }}</h3><div><span>{{ __('ui.z2') }}</span><b>{{ $reg->course->name(app()->getLocale()) }}</b></div><div><span>{{ __('ui.z4') }}</span><b style="color:#C2410C">{{ number_format($reg->amount, 0, '', ' ') }} {{ __('ui.da') }}</b></div></div>
<div class="ccpinfo"><h3>{{ __('ui.z5') }}</h3>
<div><span>CCP</span><code id="ccpn">{{ $c['ccp_number'] ?? '' }}</code><button class="copy" data-c="ccpn">{{ __('ui.z9') }}</button></div>
<div><span>{{ __('ui.z6') }}</span><b>{{ $c['ccp_name'] ?? '' }}</b></div>
<div><span>RIP</span><code id="rip">{{ $c['rip'] ?? '' }}</code><button class="copy" data-c="rip">{{ __('ui.z9') }}</button></div></div>
<a class="wa" href="{{ $wa }}" target="_blank" rel="noopener">{{ __('ui.z7') }}</a>
<p class="secure">{{ __('ui.z8') }}</p>
<p class="secure"><a href="{{ route('thanks', $reg) }}">{{ __('ui.thx_wait') }}</a></p>
</div></div></section>
@endsection
