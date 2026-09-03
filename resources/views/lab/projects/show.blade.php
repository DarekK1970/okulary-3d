@extends('layouts.app')

@section('title', $project->name . ' — ' . __('site.title'))

@push('head')
    @vite(['resources/css/lab.css', 'resources/css/lenticular-lab.css'])
@endpush

@section('content')
@php($source = $project->files->firstWhere('kind', 'source_video'))
@php($analysis = $project->jobs->where('operation', 'analyze_video')->sortByDesc('created_at')->first())
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
                <div class="lenticular-analysis-previews">
                    @foreach($project->files->filter(fn($file) => str_starts_with($file->kind, 'analysis_thumbnail_'))->sortBy('kind') as $preview)
                        <img src="{{ Storage::disk($preview->disk)->temporaryUrl($preview->path, now()->addMinutes(15)) }}" alt="Podgląd klatki {{ $loop->iteration }}">
                    @endforeach
                </div>
                <form method="post" action="{{ route('lab.projects.frames.store', ['locale' => app()->getLocale(), 'project' => $project]) }}" class="lenticular-controls">
                    @csrf
                    <div class="lenticular-control-grid">
                        <div class="lab-control"><label for="frame-start">Klatka od</label><input id="frame-start" name="start" type="number" min="0" max="{{ $source->metadata['frame_count'] - 1 }}" value="0" required></div>
                        <div class="lab-control"><label for="frame-end">Klatka do</label><input id="frame-end" name="end" type="number" min="0" max="{{ $source->metadata['frame_count'] - 1 }}" value="{{ $source->metadata['frame_count'] - 1 }}" required></div>
                        <div class="lab-control"><label for="frame-step">Co która klatka</label><input id="frame-step" name="step" type="number" min="1" value="1" required></div>
                        <input name="jpeg_quality" type="hidden" value="95">
                    </div>
                    <button class="lab-primary-button" type="submit">Przygotuj wybraną sekwencję</button>
                </form>
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
