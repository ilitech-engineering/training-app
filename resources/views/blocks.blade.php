@php $L = fn ($s) => array_values(array_filter(array_map('trim', explode("\n", (string) $s)))); @endphp
@foreach ($blocks as $b)
@php $d = $b['data'] ?? []; @endphp
@if($b['type']==='hero')
<header class="hero">
<svg class="topo" viewBox="0 0 800 500" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><g transform="translate(600 160)"><ellipse rx="60" ry="34"/><ellipse rx="120" ry="70"/><ellipse rx="190" ry="108"/><ellipse rx="270" ry="150"/><ellipse rx="360" ry="200"/><ellipse rx="460" ry="250"/></g></svg>
<div class="wrap"><div>
  @if (!empty($d['pill']))<span class="pill">{{ $d['pill'] }}</span>@endif
  <h1>{{ $d['title'] ?? '' }}<small>{{ $d['subtitle'] ?? '' }}</small></h1>
  <p class="sub">{{ $d['text'] ?? '' }}</p>
  @if (!empty($d['instructor']))<p class="by">{{ $d['instructor'] }}</p>@endif
  <a class="btn" href="#checkout">{{ $d['cta'] ?? __('ui.cta1') }}</a>@if (!empty($d['cta2']))<a class="btn ghost" href="#program">{{ $d['cta2'] }}</a>@endif
  <div class="facts">@foreach ($L($d['facts'] ?? '') as $f)<span><b>{{ $f }}</b></span>@endforeach</div>
  @if ($next)
  <div class="cd" data-cd="{{ $next->starts_at->toIso8601String() }}">@foreach (['cd1', 'cd2', 'cd3', 'cd4'] as $k)<div><b>00</b><span>{{ __('ui.' . $k) }}</span></div>@endforeach</div>
  <p class="by" style="margin-top:16px">📅 {{ $next->starts_at->locale(app()->getLocale())->translatedFormat('l d F Y · H:i') }} — {{ __('ui.' . $next->mode) }}@if ($next->location) · {{ $next->location }}@endif</p>
  @endif
</div>
<div class="stack" aria-hidden="true"><div class="layer">CSV · GPS<span>raw</span></div><div class="layer">Geodatabase<span>clean</span></div><div class="layer">Spatial Analysis<span>model</span></div><div class="layer">Layout · PDF<span>deliver</span></div></div>
</div></header>

@elseif($b['type']==='cards')
<section @class(['dark' => ($d['variant'] ?? '') === 'dark'])><div class="wrap">
<h2>{{ $d['title'] ?? '' }}</h2>@if (!empty($d['lead']))<p class="lead">{{ $d['lead'] }}</p>@endif
<div class="grid">@foreach ($d['items'] ?? [] as $c)<div class="card"><span class="ico">{{ $c['icon'] ?? '' }}</span><h3>{{ $c['title'] ?? '' }}</h3><p>{{ $c['text'] ?? '' }}</p></div>@endforeach</div>
</div></section>

@elseif($b['type']==='checklist')
<section><div class="wrap"><h2>{{ $d['title'] ?? '' }}</h2><br><ul class="tick">@foreach ($L($d['items'] ?? '') as $i)<li>{{ $i }}</li>@endforeach</ul></div></section>

@elseif($b['type']==='compare')
<section style="padding-top:0"><div class="wrap"><div class="two">
<div class="card"><h3>{{ $d['bad_title'] ?? '' }}</h3><ul class="bad">@foreach ($L($d['bad'] ?? '') as $i)<li>{{ $i }}</li>@endforeach</ul></div>
<div class="card" style="border-color:var(--amber)"><h3>{{ $d['good_title'] ?? '' }}</h3><ul class="good">@foreach ($L($d['good'] ?? '') as $i)<li>{{ $i }}</li>@endforeach</ul></div>
</div></div></section>

@elseif($b['type']==='curriculum')
<section id="program"><div class="wrap"><h2>{{ $d['title'] ?? '' }}</h2><p class="lead">{{ $d['lead'] ?? '' }}</p>
@foreach ($d['weeks'] ?? [] as $i => $w)
<details @class(['final' => $loop->last]) @if ($loop->first) open @endif><summary><i>{{ $i + 1 }}</i><span>{{ $w['title'] ?? '' }}</span></summary><ul>@foreach ($L($w['points'] ?? '') as $p)<li>{{ $p }}</li>@endforeach</ul></details>
@endforeach
</div></section>

@elseif($b['type']==='bonuses')
<section><div class="wrap"><h2>{{ $d['title'] ?? '' }}</h2><br><div class="grid">
@foreach ($d['items'] ?? [] as $c)<div class="card"><span class="ico">{{ $c['icon'] ?? '' }}</span><h3>{{ $c['title'] ?? '' }}</h3><p>{{ $c['text'] ?? '' }}</p><span class="val">{{ $c['value'] ?? '' }}</span></div>@endforeach
</div><p style="margin-top:18px;font-weight:700;color:var(--deep)">{{ $d['total'] ?? '' }}</p></div></section>

@elseif($b['type']==='instructor')
<section class="dark"><div class="wrap">
<div class="who"><div class="av"><img src="/img/logo-light.png" alt="" width="64" height="64"></div><div><h2 style="margin:0">{{ $d['title'] ?? '' }}</h2><b>{{ $d['name'] ?? '' }}</b>@if (!empty($d['linkedin'])) · <a href="{{ $d['linkedin'] }}" rel="noopener" target="_blank">LinkedIn</a>@endif</div></div>
<p class="lead" style="margin:16px 0">{{ $d['bio'] ?? '' }}</p>
<div class="stats">@foreach ($L($d['stats'] ?? '') as $s)<div>{{ $s }}</div>@endforeach</div>
<div class="chips">@foreach (array_filter(array_map('trim', explode(',', $d['skills'] ?? ''))) as $s)<span>{{ $s }}</span>@endforeach</div>
@if (!empty($d['cases']))<h3 style="margin-top:18px">{{ $d['cases_title'] ?? '' }}</h3><ul class="tick" style="color:#D3E6F3">@foreach ($L($d['cases']) as $i)<li style="border-color:#0d4a6e">{{ $i }}</li>@endforeach</ul>@endif
</div></section>

@elseif($b['type']==='guarantee')
<section><div class="wrap"><div class="guar"><span class="ico">🛡️</span><div><h2 style="font-size:1.5rem">{{ $d['title'] ?? '' }}</h2><p class="lead" style="margin:0">{{ $d['text'] ?? '' }}</p></div></div></div></section>

@elseif($b['type']==='faq')
<section><div class="wrap"><h2>{{ $d['title'] ?? '' }}</h2>
@foreach ($d['items'] ?? [] as $q)<details><summary><span>{{ $q['q'] ?? '' }}</span></summary><ul><li style="list-style:none;margin:0">{{ $q['a'] ?? '' }}</li></ul></details>@endforeach
</div></section>

@elseif($b['type']==='testimonials')
<section><div class="wrap"><h2>{{ $d['title'] ?? '' }}</h2><br><div class="grid">@foreach ($d['items'] ?? [] as $t)<div class="card"><p>« {{ $t['text'] ?? '' }} »</p><b>{{ $t['name'] ?? '' }}</b></div>@endforeach</div></div></section>

@elseif($b['type']==='richtext')
<section><div class="wrap">{!! $d['content'] ?? '' !!}</div></section>
@endif
@endforeach
@push('head')
@if (collect($blocks)->contains(fn ($b) => $b['type'] === 'faq'))
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => collect($blocks)->firstWhere('type', 'faq')['data']['items'] ? collect(collect($blocks)->firstWhere('type', 'faq')['data']['items'])->map(fn ($q) => ['@type' => 'Question', 'name' => $q['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q['a']]])->all() : []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endpush
