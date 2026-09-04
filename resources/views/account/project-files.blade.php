@extends('layouts.app')

@section('title', __('portal_auth.project_files.title') . ' — ' . $project->name)

@section('content')
<section class="account-page">
    <div class="site-container account-container">
        <a class="account-project-files-back" href="{{ route('account', ['locale' => app()->getLocale()]) }}">← {{ __('portal_auth.project_files.back') }}</a>
        <div class="account-heading"><div><span class="auth-kicker">{{ __('portal_auth.projects.title') }}</span><h1>{{ $project->name }}</h1><p>{{ __('portal_auth.project_files.description') }}</p></div>@if($project->files->contains(fn ($file) => Storage::disk($file->disk)->exists($file->path)) || $project->jobs->flatMap->artifacts->contains(fn ($artifact) => Storage::disk($artifact->disk)->exists($artifact->path)))<a class="account-project-zip-button" href="{{ route('lab.projects.archive', ['locale' => app()->getLocale(), 'project' => $project]) }}">{{ __('portal_auth.project_files.download_all') }}</a>@endif</div>

        <section class="account-card account-projects-card">
            <div class="account-projects-table-wrap"><table class="account-projects-table account-project-files-table"><thead><tr><th>{{ __('portal_auth.project_files.kind') }}</th><th>{{ __('portal_auth.project_files.file') }}</th><th>{{ __('portal_auth.project_files.type') }}</th><th>{{ __('portal_auth.project_files.size') }}</th><th>{{ __('portal_auth.project_files.preview') }}</th><th>{{ __('portal_auth.project_files.actions') }}</th></tr></thead><tbody>
            @foreach($project->files->sortByDesc('created_at') as $file)
                <tr><td><code>{{ $file->kind }}</code></td><td>{{ $file->original_name }}</td><td>{{ $file->media_type ?: '—' }}</td><td>{{ number_format($file->size_bytes / 1024 / 1024, 2, ',', ' ') }} MB</td><td>@if(str_starts_with((string) $file->media_type, 'image/') && Storage::disk($file->disk)->exists($file->path))<img class="account-project-preview" src="{{ route('lab.projects.files.show', ['locale' => app()->getLocale(), 'project' => $project, 'file' => $file]) }}" alt="">@else—@endif</td><td>@if(Storage::disk($file->disk)->exists($file->path))<a class="account-project-file-download" href="{{ route('lab.projects.files.show', ['locale' => app()->getLocale(), 'project' => $project, 'file' => $file, 'download' => 1]) }}" title="{{ __('portal_auth.project_files.download') }}" aria-label="{{ __('portal_auth.project_files.download') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 19h14"/></svg></a>@else<span class="account-project-no-preview">{{ __('portal_auth.project_files.missing') }}</span>@endif</td></tr>
            @endforeach
            @foreach($project->jobs->flatMap->artifacts->sortByDesc('created_at') as $artifact)
                <tr><td><code>{{ $artifact->kind }}</code></td><td>{{ basename($artifact->path) }}</td><td>{{ $artifact->media_type }}</td><td>{{ number_format($artifact->size_bytes / 1024 / 1024, 2, ',', ' ') }} MB</td><td>—</td><td>@if(Storage::disk($artifact->disk)->exists($artifact->path))<a class="account-project-file-download" href="{{ route('lab.projects.artifacts.show', ['locale' => app()->getLocale(), 'project' => $project, 'artifact' => $artifact]) }}" title="{{ __('portal_auth.project_files.download') }}" aria-label="{{ __('portal_auth.project_files.download') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 19h14"/></svg></a>@else<span class="account-project-no-preview">{{ __('portal_auth.project_files.missing') }}</span>@endif</td></tr>
            @endforeach
            @if($project->files->isEmpty() && $project->jobs->flatMap->artifacts->isEmpty())<tr><td colspan="6" class="account-projects-empty">{{ __('portal_auth.project_files.empty') }}</td></tr>@endif
            </tbody></table></div>
        </section>
    </div>
</section>
@endsection
