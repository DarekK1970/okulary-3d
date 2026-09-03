@extends('layouts.app')
@section('title', $project->name . ' — ' . __('site.title'))
@push('head')
    @vite(['resources/css/lab.css', 'resources/css/lenticular-lab.css', 'resources/js/lenticular-project.js'])
@endpush
@section('content')
@php($source = $project->files->firstWhere('kind', 'source_video'))
@php($analysis = $project->jobs->where('operation', 'analyze_video')->sortByDesc('created_at')->first())
@php($extraction = $project->jobs->where('operation', 'extract_video_frames')->sortByDesc('created_at')->first())
@php($alignment = $project->jobs->where('operation', 'align_sequence')->sortByDesc('created_at')->first())
@php($analysisPreviews = $project->files->filter(fn($file) => str_starts_with($file->kind, 'analysis_thumbnail_'))->sortBy('kind')->values())
@php($timelinePreviews = $project->files->filter(fn($file) => str_starts_with($file->kind, 'timeline_thumbnail_'))->sortBy('kind')->values())
@php($alignmentPreviews = $project->files->filter(fn($file) => str_starts_with($file->kind, 'alignment_preview_'))->sortBy('kind')->values())
@php($framesReady = $extraction?->status === \App\Enums\LenticularJobStatus::Completed)
@php($printSettings = array_merge(['print_size' => 'A4', 'dpi' => 1200, 'lpi' => 60, 'max_frames' => 20], $project->settings ?? []))
@php($savedSelection = $printSettings['selection'] ?? $extraction?->parameters['selection'] ?? null)
@php($selectionTargets = is_array($savedSelection) ? [$savedSelection['start'], (int) round(($savedSelection['start'] + $savedSelection['end']) / 2), $savedSelection['end']] : [])
@php($selectedPreviews = collect($selectionTargets)->map(fn($target) => $timelinePreviews->sortBy(fn($file) => abs(($file->metadata['frame_index'] ?? 0) - $target))->first())->filter()->unique('id')->values())
@php($stagePreviews = $selectedPreviews->count() === 3 ? $selectedPreviews : $analysisPreviews)
<section class="lab-workspace-page lenticular-page"><div class="container"><div class="lenticular-panel">
    <div class="lenticular-panel-heading"><div><span class="lab-kicker">PRO / VIDEO</span><h1>{{ $project->name }}</h1><p>{{ $printSettings['print_size'] }} · {{ $printSettings['dpi'] }} DPI · {{ $printSettings['lpi'] }} LPI · {{ __('lenticular_projects.available_frames') }} <strong>{{ $printSettings['max_frames'] }}</strong></p></div></div>
    <ol class="lenticular-stepper"><li class="is-complete"><span>1</span>{{ __('lenticular_projects.step_1') }}</li><li @class(['is-active' => !$framesReady, 'is-complete' => $framesReady])><span>2</span>{{ __('lenticular_projects.step_2') }}</li><li @class(['is-active' => $framesReady, 'is-locked' => !$framesReady])><span>3</span>{{ __('lenticular_projects.step_3') }}</li></ol>
    @if(session('status'))<p class="lenticular-export-note">{{ session('status') }}</p>@endif
    @if(!$source)
        <section class="lenticular-wizard-step"><h2>{{ __('lenticular_projects.upload_video') }}</h2><p>{{ __('lenticular_projects.upload_help') }}</p><form method="post" action="{{ route('lab.projects.video.store', ['locale' => app()->getLocale(), 'project' => $project]) }}" enctype="multipart/form-data" class="lenticular-controls">@csrf<div class="lab-control"><label for="project-video">{{ __('lenticular_projects.video') }}</label><input id="project-video" name="video" type="file" accept="video/mp4,video/quicktime,video/webm" required>@error('video')<small>{{ $message }}</small>@enderror</div><button class="lab-primary-button" type="submit">{{ __('lenticular_projects.upload') }}</button></form></section>
    @elseif(!$source->metadata)
        <section class="lenticular-wizard-step"><h2>{{ __('lenticular_projects.analysis_in_progress') }}</h2><p>{{ $analysis?->stage ?? 'queued' }} · {{ $analysis?->progress ?? 0 }}%</p></section><meta http-equiv="refresh" content="5">
    @elseif(!$framesReady)
        <section class="lenticular-wizard-step"><h2>{{ __('lenticular_projects.select_range') }}</h2><div class="lenticular-result-grid"><div><span>{{ __('lenticular_projects.resolution') }}</span><strong>{{ $source->metadata['width'] }} × {{ $source->metadata['height'] }}</strong></div><div><span>{{ __('lenticular_projects.frames') }}</span><strong>{{ $source->metadata['frame_count'] }}</strong></div><div><span>FPS</span><strong>{{ number_format($source->metadata['fps'], 3, ',', ' ') }}</strong></div><div><span>{{ __('lenticular_projects.duration') }}</span><strong>{{ number_format($source->metadata['duration_seconds'], 2, ',', ' ') }} s</strong></div></div>
        @if($extraction && !$extraction->status->isTerminal())<p class="lenticular-export-note">{{ __('lenticular_projects.extracting') }} <strong>{{ $extraction->progress }}%</strong></p><meta http-equiv="refresh" content="5">
        @else
            @php($timeline = $timelinePreviews->isNotEmpty() ? $timelinePreviews : $analysisPreviews)
            <form method="post" action="{{ route('lab.projects.frames.store', ['locale' => app()->getLocale(), 'project' => $project]) }}" class="lenticular-controls" data-frame-timeline data-frame-count="{{ $source->metadata['frame_count'] }}" data-max-frames="{{ $printSettings['max_frames'] }}">@csrf
                <div class="lenticular-timeline"><div class="lenticular-timeline-selection" data-timeline-selection></div>@foreach($timeline as $preview)<figure data-frame-index="{{ $preview->metadata['frame_index'] ?? round(($loop->index * ($source->metadata['frame_count'] - 1)) / max(1, $timeline->count() - 1)) }}"><img src="{{ Storage::disk($preview->disk)->temporaryUrl($preview->path, now()->addMinutes(15)) }}" alt=""><figcaption>{{ $preview->metadata['frame_index'] ?? '' }}</figcaption></figure>@endforeach</div>
                <input class="lenticular-timeline-slider" type="range" min="0" max="{{ $source->metadata['frame_count'] - 1 }}" value="0" data-timeline-slider aria-label="{{ __('lenticular_projects.range_position') }}">
                <div class="lenticular-timeline-options"><label>{{ __('lenticular_projects.frame_step') }} <select name="step" data-frame-step>@foreach(range(1, 10) as $step)<option>{{ $step }}</option>@endforeach</select></label><p>{{ __('lenticular_projects.selected_range') }} <strong><span data-range-start>0</span>–<span data-range-end>{{ min($source->metadata['frame_count'] - 1, $printSettings['max_frames'] - 1) }}</span></strong> (<span data-selected-count>{{ min($source->metadata['frame_count'], $printSettings['max_frames']) }}</span>)</p></div>
                <input name="start" type="hidden" value="0" data-frame-start><input name="end" type="hidden" value="{{ min($source->metadata['frame_count'] - 1, $printSettings['max_frames'] - 1) }}" data-frame-end><input name="jpeg_quality" type="hidden" value="95"><button class="lab-primary-button" type="submit">{{ __('lenticular_projects.extract_frames') }}</button>
            </form>
        @endif</section>
    @else
        <section class="lenticular-wizard-step"><h2>{{ __('lenticular_projects.step_3') }}</h2><p>{{ __('lenticular_projects.alignment_help') }}</p><form method="post" action="{{ route('lab.projects.alignment.store', ['locale' => app()->getLocale(), 'project' => $project]) }}" class="lenticular-controls">@csrf<input id="z-center" name="z_center" type="hidden" value="0.5">
            <div class="lenticular-alignment-editor"><div class="lenticular-vertical-control"><output data-range-output="alignment-y">50%</output><input id="alignment-y" name="alignment_y" type="range" min="0" max="1" step="0.01" value="0.5" aria-label="{{ __('lenticular_projects.alignment_y') }}"></div><div class="lenticular-alignment-preview"><div class="lenticular-alignment-stage" data-alignment-stage data-source-width="{{ $source->metadata['width'] }}" data-source-height="{{ $source->metadata['height'] }}">@foreach($stagePreviews as $preview)<img class="lenticular-alignment-frame frame-{{ $loop->index }}" src="{{ Storage::disk($preview->disk)->temporaryUrl($preview->path, now()->addMinutes(15)) }}" alt="{{ __('lenticular_projects.preview_frame', ['number' => $loop->iteration]) }}">@endforeach<button class="lenticular-z-zone" type="button" data-z-zone aria-label="{{ __('lenticular_projects.move_z') }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50"><span></span></button></div><div class="lenticular-width-control"><label for="z-width">{{ __('lenticular_projects.z_width') }} <output data-range-output="z-width">5%</output></label><input id="z-width" name="z_width" type="range" min="0.01" max="0.5" step="0.01" value="0.05"></div></div><div class="lenticular-overlay-switches"><label><input type="checkbox" data-overlay-toggle="1"> {{ __('lenticular_projects.middle_frame') }}</label><label><input type="checkbox" data-overlay-toggle="2"> {{ __('lenticular_projects.last_frame') }}</label></div></div>
            <button class="lab-primary-button" type="submit">{{ __('lenticular_projects.auto_alignment') }}</button></form>
            @if($alignment)
                <p class="lenticular-export-note">{{ __('lenticular_projects.alignment_status') }}: <strong>{{ $alignment->stage ?? $alignment->status->value }}</strong> · {{ $alignment->progress }}%</p>
                @if(!$alignment->status->isTerminal())
                    <meta http-equiv="refresh" content="5">
                @endif
            @endif
            @if($alignmentPreviews->isNotEmpty())
                <div class="lenticular-analysis-previews">
                    @foreach($alignmentPreviews as $preview)
                        <img src="{{ Storage::disk($preview->disk)->temporaryUrl($preview->path, now()->addMinutes(15)) }}" alt="{{ __('lenticular_projects.alignment_preview', ['number' => $loop->iteration]) }}">
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div></div></section>
@endsection
