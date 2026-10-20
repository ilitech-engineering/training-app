@php $c = $course->contact ?? []; @endphp
<section class="dark" id="checkout"><div class="wrap">
<h2 style="text-align:center">{{ __('ui.y1') }}</h2><br>
@if ($sessions->isEmpty())
  <p class="lead" style="text-align:center;margin:auto">{{ __('ui.nosession') }}</p>
  @if ($course->slug !== 'atelier-gratuit' && \App\Models\Course::where('slug', 'atelier-gratuit')->where('active', true)->exists())<p style="text-align:center"><a class="btn" href="{{ route('course', 'atelier-gratuit') }}">{{ __('ui.lm3') }}</a></p>@endif
@else
<form id="f" method="POST" action="{{ route('register', $course) }}" novalidate>@csrf
<input type="hidden" name="lang" value="{{ app()->getLocale() }}">
<label>{{ __('ui.pick') }}</label>
<div class="pm">@foreach ($sessions as $s)
  <label><input type="radio" name="course_session_id" value="{{ $s->id }}" data-price="{{ $s->currentPrice() }}" @checked(old('course_session_id', $sessions->first()->id) == $s->id)>
  <span>{{ $s->starts_at->locale(app()->getLocale())->translatedFormat('d F Y · H:i') }} — {{ __('ui.' . $s->mode) }}@if ($s->location) · {{ $s->location }}@endif
  <small>{{ $s->currentPrice() ? number_format($s->currentPrice(), 0, '', ' ') . ' ' . __('ui.da') : __('ui.free') }} · {{ __('ui.seats_left', ['n' => $s->seatsLeft()]) }}</small></span></label>
@endforeach</div>
<label for="n">{{ __('ui.y2') }}</label><input id="n" name="name" value="{{ old('name') }}" autocomplete="name" required>
<label for="p">{{ __('ui.y3') }}</label><input id="p" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" autocomplete="tel" placeholder="05 / 06 / 07 ..." required>
<label for="e">{{ __('ui.y4') }}</label><input id="e" name="email" type="email" value="{{ old('email') }}" autocomplete="email">
@if (!empty($course->addons))<div class="paid-only"><label>{{ __('ui.bm0') }}</label><div class="pm">
@foreach ($course->addons as $a)<label class="bump"><input type="checkbox" name="addons[]" value="{{ $a['key'] }}" data-price="{{ $a['price'] }}" @checked(in_array($a['key'], old('addons', [])))><span>{{ $a['label'][app()->getLocale()] ?? $a['label']['fr'] ?? $a['key'] }}<small>+ {{ number_format($a['price'], 0, '', ' ') }} {{ __('ui.da') }}</small></span></label>@endforeach
</div></div>@endif
<div class="paid-only"><label>{{ __('ui.y5') }}</label><div class="pm">
  <label><input type="radio" name="method" value="cib" @checked(old('method', 'cib') === 'cib')><span>💳 {{ __('ui.m1') }}<small>{{ __('ui.m1s') }}</small></span></label>
  <label><input type="radio" name="method" value="ccp" @checked(old('method') === 'ccp')><span>🏦 {{ __('ui.m2') }}<small>{{ __('ui.m2s') }}</small></span></label>
</div></div>
<label style="display:flex;gap:10px;font-weight:400;font-size:.88rem"><input type="checkbox" name="consent" value="1" style="width:auto;margin-top:5px" @checked(old('consent', true))><span>{{ __('ui.consent') }}</span></label>
<div class="tot"><span>{{ __('ui.y6') }}</span><b><span id="total">0</span> {{ __('ui.da') }}</b></div>
<button class="btn" type="submit">{{ __('ui.y7') }}</button>
@if ($errors->any())<div class="err" role="alert">{{ $errors->first() }}</div>@endif
<p class="secure">🔒 {{ __('ui.y8') }}</p>
@if (!empty($c['whatsapp']))<p class="secure"><a href="https://wa.me/{{ $c['whatsapp'] }}?text={{ urlencode($course->name('fr')) }}" target="_blank" rel="noopener">{{ __('ui.wq') }}</a></p>@endif
</form>
@endif
</div></section>
