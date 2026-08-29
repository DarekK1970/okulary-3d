@extends('layouts.app')

@section('title', $title . ' — ' . __('site.title'))
@section('meta_description', $message)

@section('content')
<section class="newsletter-result-page">
    <div class="site-container">
        <article class="newsletter-result-card {{ $success ? 'is-success' : 'is-error' }}">
            <span class="newsletter-result-icon" aria-hidden="true">
                {{ $success ? '✓' : '!' }}
            </span>
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
            <a href="{{ route('home', ['locale' => app()->getLocale()]) }}">
                {{ __('newsletter.public.back_home') }}
            </a>
        </article>
    </div>
</section>
@endsection
