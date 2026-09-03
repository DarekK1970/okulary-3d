@extends('layouts.app')

@section('title', $project->name . ' — ' . __('site.title'))

@push('head')
    @vite(['resources/css/lab.css', 'resources/css/lenticular-lab.css', 'resources/js/lenticular-project.js'])
@endpush

@section('content')
@php($source = $project->files->firstWhere('kind', 'source_video'))
@php($analysis = $project->jobs->where('operation', 'analyze_video')->sortByDesc('created_at')->first())
@php($alignment = $project->jobs->where('operation', 'align_sequence')->sortByDesc('created_at')->first())
@php($analysisPreviews = $project->files->filter(fn($file) => str_starts_with($file->kind, 'analysis_thumbnail_'))->sortBy('kind')->values())
@php($alignmentPreviews = $project->files->filter(fn($file) => str_starts_with($file->kind, 'alignment_preview_'))->sortBy('kind')->values())
<section class="lab-workspace-page lenticular-page">
    <div class="container">
        <div class="lenticular-panel">
            <div class="lenticular-panel-heading"><div><span class="lab-kicker">PRO / VIDEO</span><h1>{{ $project->name }}</h1><p>Status analizy: <strong>{{ $analysis?->stage ?? $analysis?->status?->value ?? 'queued' }}</strong> · {{ $analysis?->progress ?? 0 }}%</p></div></div>
            @if(session('status'))<p class="lenticular-export-note">{{ session('status') }}</p>@endif
            @if($source?->metadata)
                <div class="lenticular-result-grid">
                    <div><span>Rozdzielczość</span><strong>{{ $source->metadata['width'] }} × {{ $source->metadata['height'] }}</strong></div>
                    <div><span>Klatki</span><strong>{{ $source->metadata['frame_count'] }}</strong></div>
                    <div><span>FPS</span><strong>{{ number_format($source->metadata['fps'], 3, ',', ' ') }}</strong></div>
                    <div><span>Czas</span><strong>{{ number_format($source->metadata['duration_seconds'], 3, ',', ' ') }} s</strong></div>
                </div>
                <div class="lenticular-alignment-stage" data-alignment-stage>
                    @foreach($analysisPreviews as $preview)
                        <img class="lenticular-alignment-frame frame-{{ $loop->index }}" src="{{ Storage::disk($preview->disk)->temporaryUrl($preview->path, now()->addMinutes(15)) }}" alt="{{ __('lenticular_projects.preview_frame', ['number' => $loop->iteration]) }}">
                    @endforeach
                    <span class="lenticular-z-zone" data-z-zone><span data-alignment-line></span></span>
                </div>
                <div class="lenticular-overlay-switches">
                    <label><input type="checkbox" data-overlay-toggle="1"> {{ __('lenticular_projects.middle_frame') }}</label>
                    <label><input type="checkbox" data-overlay-toggle="2"> {{ __('lenticular_projects.last_frame') }}</label>
                </div>
                <form method="post" action="{{ route('lab.projects.frames.store', ['locale' => app()->getLocale(), 'project' => $project]) }}" class="lenticular-controls">
                    @csrf
                    <div class="lenticular-control-grid">
                        <div class="lab-control"><label for="frame-start">Klatka od</label><input id="frame-start" name="start" type="number" min="0" max="{{ $source->metadata['frame_count'] - 1 }}" value="0" required></div>
                        <div class="lab-control"><label for="frame-end">Klatka do</label><input id="frame-end" name="end" type="number" min="0" max="{{ $source->metadata['frame_count'] - 1 }}" value="{{ $source->metadata['frame_count'] - 1 }}" required></div>
                        <div class="lab-control"><label for="frame-step">Co która klatka</label><input id="frame-step" name="step" type="number" min="1" value="1" required></div>
                        <input name="jpeg_quality" type="hidden" value="95">
                        <div class="lab-control"><label for="z-center">{{ __('lenticular_projects.z_position') }} <output data-range-output="z-center">50%</output></label><input id="z-center" name="z_center" type="range" min="0" max="1" step="0.01" value="0.5"></div>
                        <div class="lab-control"><label for="z-width">{{ __('lenticular_projects.z_width') }} <output data-range-output="z-width">5%</output></label><input id="z-width" name="z_width" type="range" min="0.01" max="0.5" step="0.01" value="0.05"></div>
                        <div class="lab-control"><label for="alignment-y">{{ __('lenticular_projects.alignment_y') }} <output data-range-output="alignment-y">50%</output></label><input id="alignment-y" name="alignment_y" type="range" min="0" max="1" step="0.01" value="0.5"></div>
                    </div>
                    <button class="lab-primary-button" type="submit">{{ __('lenticular_projects.auto_alignment') }}</button>
                </form>
                @if($alignment)
                    <p class="lenticular-export-note">{{ __('lenticular_projects.alignment_status') }}: <strong>{{ $alignment->stage ?? $alignment->status->value }}</strong> · {{ $alignment->progress }}%</p>
                @endif
                @if($alignmentPreviews->isNotEmpty())
                    <div class="lenticular-analysis-previews">
                        @foreach($alignmentPreviews as $preview)
                            <img src="{{ Storage::disk($preview->disk)->temporaryUrl($preview->path, now()->addMinutes(15)) }}" alt="{{ __('lenticular_projects.alignment_preview', ['number' => $loop->iteration]) }}">
                        @endforeach
                    </div>
                @endif
            @elseif($analysis?->status === \App\Enums\LenticularJobStatus::Failed)
                <p>{{ $analysis->error_message }}</p>
            @else
                <p>Film oczekuje na analizę. Strona pokaże parametry i miniatury po zakończeniu zadania.</p>
                <meta http-equiv="refresh" content="5">
            @endif
        </div>
    </div>
</section>
@endsection
