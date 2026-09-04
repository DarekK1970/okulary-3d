@extends('admin.layout')

@section('title', __('admin.users.project_files.title') . ' — ' . $project->name)
@section('page_heading', __('admin.users.project_files.title'))

@section('content')
    <a class="admin-users-back" href="{{ route('admin.users.projects', $user) }}">← {{ __('admin.users.project_files.back') }}</a>
    <section class="admin-users-head"><div><span class="admin-eyebrow">{{ $user->name }}</span><h1>{{ $project->name }}</h1><p>{{ __('admin.users.project_files.description') }}</p></div></section>

    <div class="admin-users-table-wrap"><table class="admin-users-table admin-project-files-table"><thead><tr><th>{{ __('admin.users.project_files.kind') }}</th><th>{{ __('admin.users.project_files.file') }}</th><th>{{ __('admin.users.project_files.type') }}</th><th>{{ __('admin.users.project_files.size') }}</th><th>{{ __('admin.users.project_files.preview') }}</th><th>{{ __('admin.users.project_files.actions') }}</th></tr></thead><tbody>
    @foreach($project->files->sortByDesc('created_at') as $file)
        <tr><td><code>{{ $file->kind }}</code></td><td>{{ $file->original_name }}</td><td>{{ $file->media_type ?: '—' }}</td><td>{{ number_format($file->size_bytes / 1024 / 1024, 2, ',', ' ') }} MB</td><td>@if(str_starts_with((string) $file->media_type, 'image/') && Storage::disk($file->disk)->exists($file->path))<img class="admin-project-preview" src="{{ route('admin.users.projects.files.show', [$user, $project, $file]) }}" alt="">@else—@endif</td><td>@if(Storage::disk($file->disk)->exists($file->path))<a class="admin-user-action-icon" href="{{ route('admin.users.projects.files.show', [$user, $project, $file, 'download' => 1]) }}" title="{{ __('admin.users.project_files.download') }}" aria-label="{{ __('admin.users.project_files.download') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 19h14"/></svg></a>@else<span class="admin-project-no-preview">{{ __('admin.users.project_files.missing') }}</span>@endif</td></tr>
    @endforeach
    @foreach($project->jobs->flatMap->artifacts->sortByDesc('created_at') as $artifact)
        <tr><td><code>{{ $artifact->kind }}</code></td><td>{{ basename($artifact->path) }}</td><td>{{ $artifact->media_type }}</td><td>{{ number_format($artifact->size_bytes / 1024 / 1024, 2, ',', ' ') }} MB</td><td>—</td><td>@if(Storage::disk($artifact->disk)->exists($artifact->path))<a class="admin-user-action-icon" href="{{ route('admin.users.projects.artifacts.show', [$user, $project, $artifact]) }}" title="{{ __('admin.users.project_files.download') }}" aria-label="{{ __('admin.users.project_files.download') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 19h14"/></svg></a>@else<span class="admin-project-no-preview">{{ __('admin.users.project_files.missing') }}</span>@endif</td></tr>
    @endforeach
    @if($project->files->isEmpty() && $project->jobs->flatMap->artifacts->isEmpty())<tr><td colspan="6" class="admin-users-empty">{{ __('admin.users.project_files.empty') }}</td></tr>@endif
    </tbody></table></div>
@endsection
