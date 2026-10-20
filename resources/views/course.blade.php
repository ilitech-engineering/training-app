@extends('layout')
@section('title', $course->seo['title'][$loc] ?? $course->name($loc))
@section('desc', $course->seo['description'][$loc] ?? '')
@push('head')
@foreach (config('training.locales') as $l)<link rel="alternate" hreflang="{{ $l }}" href="{{ route('course', $course->slug) }}?lang={{ $l }}">@endforeach
@php
$ld = ['@context' => 'https://schema.org', '@type' => 'Course', 'name' => $course->name($loc), 'inLanguage' => $loc,
  'provider' => ['@type' => 'Organization', 'name' => 'ILITECH TRAINING', 'logo' => url('/img/logo.png')],
  'hasCourseInstance' => $sessions->map(fn ($s) => array_filter(['@type' => 'CourseInstance', 'courseMode' => $s->mode === 'offline' ? 'onsite' : 'online', 'startDate' => $s->starts_at->toIso8601String(), 'location' => $s->location]))->values()];
@endphp
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
@include('blocks', ['blocks' => collect($course->content[$loc] ?? $course->content['ar'] ?? [])->values(), 'next' => $sessions->first()])
@include('checkout')
<div class="sticky"><div><b>@if ($sessions->first()){{ $sessions->first()->currentPrice() ? number_format($sessions->first()->currentPrice(), 0, '', ' ') . ' ' . __('ui.da') : __('ui.free') }}@endif</b></div><a class="btn" href="#checkout">{{ __('ui.cta1') }}</a></div>
@endsection
