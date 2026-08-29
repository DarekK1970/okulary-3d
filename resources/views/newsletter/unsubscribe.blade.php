@extends('layouts.app')

@section('title', __('newsletter.public.unsubscribe_title') . ' — ' . __('site.title'))
@section('meta_description', __('newsletter.public.unsubscribe_prompt'))

@section('content')
<section class="newsletter-result-page">
    <div class="site-container">
        <article class="newsletter-result-card {{ $valid ? '' : 'is-error' }}">
            <span class="newsletter-result-icon" aria-hidden="true">{{ $valid ? '✉' : '!' }}</span>
            <h1>{{ $valid ? __('newsletter.public.unsubscribe_title') : __('newsletter.public.invalid_title') }}</h1>
            <p>{{ $valid ? __('newsletter.public.unsubscribe_prompt') : __('newsletter.public.invalid_text') }}</p>

            @if ($valid)
                <form method="post" action="{{ route('newsletter.unsubscribe', [
                    'locale' => app()->getLocale(),
                    'subscriber' => $subscriber,
                    'token' => $token,
                ]) }}">
                    @csrf
                    <button class="newsletter-unsubscribe-button" type="submit">
                        {{ __('newsletter.public.unsubscribe_button') }}
                    </button>
                </form>
            @else
                <a href="{{ route('home', ['locale' => app()->getLocale()]) }}">
                    {{ __('newsletter.public.back_home') }}
                </a>
            @endif
        </article>
    </div>
</section>
@endsection
