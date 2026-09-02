@extends('layouts.app')

@section('title', $section->title() . ' — ' . __('site.title'))
@section('meta_description', $section->description())

@section('content')
    <section class="home-section home-latest-publications">
        <div class="site-container">
            <div class="section-heading-row">
                <div>
                    <span class="section-kicker">{{ $section->kicker() }}</span>
                    <h1>{{ $section->title() }}</h1>
                    <p class="section-intro">{{ $section->description() }}</p>
                </div>
            </div>

            @if ($articles->isNotEmpty())
                <div class="home-publications-grid">
                    @foreach ($articles as $index => $article)
                        <x-publication-card :article="$article" :index="$index" />
                    @endforeach
                </div>

                @if ($articles->hasPages())
                    <div style="margin-top: 32px;">
                        {{ $articles->links() }}
                    </div>
                @endif
            @else
                <div class="article-home-empty">
                    <p>{{ __('article_sections.empty') }}</p>
                </div>
            @endif
        </div>
    </section>
@endsection
