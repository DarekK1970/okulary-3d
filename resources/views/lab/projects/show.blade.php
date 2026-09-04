@extends('layouts.app')
@section('title', $project->name . ' — ' . __('site.title'))
@push('head')
    @vite(['resources/css/lab.css', 'resources/css/lenticular-lab.css', 'resources/js/lenticular-project.js'])
@endpush
@section('content')
@php($source = $project->files->firstWhere('kind', 'source_video') ?? $project->files->firstWhere('kind', 'source_sequence'))
@php($isSequence = $source?->kind === 'source_sequence')
@php($analysis = $project->jobs->where('operation', 'analyze_video')->sortByDesc('created_at')->first())
@php($extraction = $project->jobs->whereIn('operation', ['extract_video_frames', 'import_sequence'])->sortByDesc('id')->first())
@php($alignment = $project->jobs->where('operation', 'align_sequence')->filter(fn($job) => $extraction && strcmp((string) $job->id, (string) $extraction->id) > 0)->sortByDesc('id')->first())
@php($finalization = $project->jobs->where('operation', 'finalize_sequence')->filter(fn($job) => $alignment && strcmp((string) $job->id, (string) $alignment->id) > 0)->sortByDesc('id')->first())
@php($analysisPreviews = $project->files->filter(fn($file) => str_starts_with($file->kind, 'analysis_thumbnail_'))->sortBy('kind')->values())
@php($timelinePreviews = $project->files->filter(fn($file) => str_starts_with($file->kind, 'timeline_thumbnail_'))->sortBy(fn($file) => (int) ($file->metadata['frame_index'] ?? 0))->values())
@php($alignmentPreviews = $project->files->filter(fn($file) => str_starts_with($file->kind, 'alignment_preview_'))->sortBy('kind')->values())
@php($alignmentFrames = $project->files->filter(fn($file) => str_starts_with($file->kind, 'alignment_frame_'))->sortBy(fn($file) => (int) ($file->metadata['sequence_index'] ?? 0))->values())
@php($finalPreviews = $project->files->filter(fn($file) => str_starts_with($file->kind, 'final_preview_'))->sortBy(fn($file) => (int) ($file->metadata['sequence_index'] ?? 0))->values())
@php($sequencePreviews = $project->files->filter(fn($file) => str_starts_with($file->kind, 'sequence_preview_'))->sortBy(fn($file) => (int) ($file->metadata['sequence_index'] ?? 0))->values())
@php($framesReady = $extraction?->status === \App\Enums\LenticularJobStatus::Completed)
@php($showFrameSelection = !$isSequence && $source?->metadata && (request()->integer('step') === 2 || !$framesReady))
@php($printSettings = array_merge(['print_size' => 'A4', 'dpi' => 1200, 'lpi' => 60, 'max_frames' => 20], $project->settings ?? []))
@php($savedSelection = $printSettings['selection'] ?? $extraction?->parameters['selection'] ?? null)
@php($selectionTargets = is_array($savedSelection) ? [$savedSelection['start'], (int) round(($savedSelection['start'] + $savedSelection['end']) / 2), $savedSelection['end']] : [])
@php($selectedPreviews = collect($selectionTargets)->map(fn($target) => $timelinePreviews->sortBy(fn($file) => abs(($file->metadata['frame_index'] ?? 0) - $target))->first())->filter()->unique('id')->values())
@php($stagePreviews = $isSequence ? $sequencePreviews : ($selectedPreviews->count() === 3 ? $selectedPreviews : $analysisPreviews))
<section class="lab-workspace-page lenticular-page"><div class="container"><div class="lenticular-panel">
    <div class="lenticular-panel-heading"><div><span class="lab-kicker">FLIP / MORFING / ZOOM</span><h1>{{ $project->name }}</h1><p>{{ $printSettings['print_size'] }} · {{ $printSettings['dpi'] }} DPI · {{ $printSettings['lpi'] }} LPI · {{ __('lenticular_projects.horizontal_lenses') }} · 2–{{ $printSettings['max_frames'] }} {{ __('lenticular_projects.frames') }}</p></div></div>
    <ol class="lenticular-stepper"><li class="is-complete"><span>1</span>{{ __('lenticular_projects.step_1') }}</li><li @class(['is-active' => $showFrameSelection, 'is-complete' => $framesReady && !$showFrameSelection])><a href="{{ route('lab.projects.show', ['locale' => app()->getLocale(), 'project' => $project, 'step' => 2]) }}" aria-label="{{ __('lenticular_projects.return_to_frame_selection') }}"><span>2</span>{{ __('lenticular_projects.step_2') }}</a></li><li @class(['is-active' => $framesReady && !$showFrameSelection, 'is-locked' => !$framesReady || $showFrameSelection])><span>3</span>{{ __('lenticular_projects.step_3') }}</li></ol>
    @if(session('status'))<p class="lenticular-export-note">{{ session('status') }}</p>@endif
    @if(!$source)
        <section class="lenticular-wizard-step"><h2>{{ __('lenticular_projects.upload_source') }}</h2><p>{{ __('lenticular_projects.upload_help') }}</p><div class="lenticular-source-options"><form method="post" action="{{ route('lab.projects.video.store', ['locale' => app()->getLocale(), 'project' => $project]) }}" enctype="multipart/form-data" class="lenticular-controls">@csrf<div class="lab-control"><label for="project-video">{{ __('lenticular_projects.video') }}</label><input id="project-video" name="video" type="file" accept="video/mp4,video/quicktime,video/webm" required>@error('video')<small>{{ $message }}</small>@enderror</div><button class="lab-primary-button" type="submit">{{ __('lenticular_projects.upload_video') }}</button></form><form method="post" action="{{ route('lab.projects.images.store', ['locale' => app()->getLocale(), 'project' => $project]) }}" enctype="multipart/form-data" class="lenticular-controls">@csrf<div class="lab-control"><label for="project-images">{{ __('lenticular_projects.images_2_6') }}</label><input id="project-images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required>@error('images')<small>{{ $message }}</small>@enderror</div><button class="lab-primary-button" type="submit">{{ __('lenticular_projects.upload_images') }}</button></form></div></section>
    @elseif(!$source->metadata)
        <section class="lenticular-wizard-step"><h2>{{ __('lenticular_projects.analysis_in_progress') }}</h2><p>{{ $analysis?->stage ?? 'queued' }} · {{ $analysis?->progress ?? 0 }}%</p></section><meta http-equiv="refresh" content="5">
    @elseif($showFrameSelection)
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
        <section class="lenticular-wizard-step"><h2>{{ __('lenticular_projects.step_3') }}</h2>
        @if(!$alignment || $alignment->status !== \App\Enums\LenticularJobStatus::Completed)
            <p>{{ __('lenticular_projects.alignment_help') }}</p><form method="post" action="{{ route('lab.projects.alignment.store', ['locale' => app()->getLocale(), 'project' => $project]) }}" class="lenticular-controls">@csrf<input id="z-center" name="z_center" type="hidden" value="0.5">
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
        @elseif(!$finalization || $finalization->status !== \App\Enums\LenticularJobStatus::Completed)
            @if($finalization && !$finalization->status->isTerminal())
                <p class="lenticular-export-note">{{ __('lenticular_projects.finalization_in_progress') }} <strong>{{ $finalization->progress }}%</strong></p><meta http-equiv="refresh" content="5">
            @else
                <p>{{ __('lenticular_projects.crop_help') }}</p>
                <form method="post" action="{{ route('lab.projects.finalize.store', ['locale' => app()->getLocale(), 'project' => $project]) }}" class="lenticular-controls" data-crop-form>@csrf
                    <div class="lenticular-crop-toolbar"><label for="crop-ratio">{{ __('lenticular_projects.crop_ratio') }}</label><select id="crop-ratio" data-crop-ratio><option value="free">{{ __('lenticular_projects.free_ratio') }}</option><option value="1">1:1</option><option value="1.333333">4:3</option><option value="0.75">3:4</option><option value="1.777778">16:9</option></select></div>
                    <div class="lenticular-crop-stage" data-crop-stage>
                        @if($alignmentFrames->isNotEmpty())<img src="{{ Storage::disk($alignmentFrames->first()->disk)->temporaryUrl($alignmentFrames->first()->path, now()->addMinutes(15)) }}" alt="{{ __('lenticular_projects.crop_preview') }}">@endif
                        <div class="lenticular-crop-selection" data-crop-selection></div>
                    </div>
                    <input name="crop_x" type="hidden" value="0" data-crop-x><input name="crop_y" type="hidden" value="0" data-crop-y><input name="crop_width" type="hidden" value="1" data-crop-width><input name="crop_height" type="hidden" value="1" data-crop-height>
                    <label class="lenticular-reverse"><input name="reverse" type="checkbox" value="1"> {{ __('lenticular_projects.reverse_sequence') }}</label>
                    <button class="lab-primary-button" type="submit">{{ __('lenticular_projects.save_frames') }}</button>
                </form>
                @if($finalization?->status === \App\Enums\LenticularJobStatus::Failed)<p class="lenticular-warning">{{ $finalization->error_message }}</p>@endif
            @endif
        @else
            <p>{{ __('lenticular_projects.animation_help') }}</p>
            <div class="lenticular-final-result">
                <div class="lenticular-sequence-animation" data-sequence-animation>@foreach($finalPreviews as $preview)<img @class(['is-visible' => $loop->first]) src="{{ Storage::disk($preview->disk)->temporaryUrl($preview->path, now()->addMinutes(15)) }}" alt="{{ __('lenticular_projects.preview_frame', ['number' => $loop->iteration]) }}">@endforeach</div>
                <button class="lab-secondary-button" type="button" data-animation-toggle data-pause-label="{{ __('lenticular_projects.pause_animation') }}" data-play-label="{{ __('lenticular_projects.play_animation') }}">{{ __('lenticular_projects.pause_animation') }}</button>
                <a class="lab-primary-button" href="{{ route('lab.projects.download', ['locale' => app()->getLocale(), 'project' => $project]) }}">{{ __('lenticular_projects.download_jpg') }}</a>
            </div>
        @endif
        </section>
    @endif
</div></div></section>
@endsection
