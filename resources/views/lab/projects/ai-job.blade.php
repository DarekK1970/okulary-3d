@extends('layouts.app')
@section('title', __('lenticular_studio.job.title').' — '.__('site.title'))
@push('head')@vite(['resources/css/lab.css', 'resources/css/lenticular-studio.css', 'resources/css/lenticular-ai-pair.css', 'resources/js/lenticular-studio.js'])@endpush
@section('content')
<section class="studio-page"><div class="site-container studio-job-page">
    <span class="lab-kicker">AI LENTICULAR STUDIO</span><h1>{{ __('lenticular_studio.job.title') }}</h1><p>{{ $job->lenticularProject->name }}</p>
    <div class="studio-job-status is-{{ $job->status->value }}"><span class="studio-job-spinner"></span><div><strong>{{ __('lenticular_studio.job.statuses.'.$job->status->value) }}</strong><p>{{ $job->status->isTerminal() ? __('lenticular_studio.job.finished_help') : __('lenticular_studio.job.waiting_help') }}</p></div></div>
    @if($job->status === \App\Enums\FalAiJobStatus::Succeeded)<a class="lab-primary-button" href="{{ route('lab.projects.show', ['locale' => app()->getLocale(), 'project' => $job->lenticularProject]) }}">{{ __('lenticular_studio.job.continue') }} →</a>@elseif(!$job->status->isTerminal())<span data-studio-job-refresh data-refresh-after="10000"></span>@endif
    @if($job->status === \App\Enums\FalAiJobStatus::Failed)<p class="studio-job-error">{{ __('lenticular_studio.job.failed_help') }}</p>@endif
</div></section>
@endsection
