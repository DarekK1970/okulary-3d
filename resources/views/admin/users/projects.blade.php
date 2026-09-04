@extends('admin.layout')

@section('title', __('admin.users.projects.title') . ' — ' . $user->name)
@section('page_heading', __('admin.users.projects.title'))

@section('content')
    <a class="admin-users-back" href="{{ route('admin.users') }}">← {{ __('admin.users.projects.back') }}</a>
    <section class="admin-users-head">
        <div><span class="admin-eyebrow">{{ __('admin.users.projects.kicker') }}</span><h1>{{ $user->name }}</h1><p>{{ $user->email }}</p></div>
        <strong>{{ trans_choice('admin.users.projects.results', $projects->total(), ['count' => $projects->total()]) }}</strong>
    </section>

    <div class="admin-users-table-wrap"><table class="admin-users-table admin-projects-table"><thead><tr><th>LP</th><th>{{ __('admin.users.projects.created') }}</th><th>{{ __('admin.users.projects.name') }}</th><th>{{ __('admin.users.projects.preview') }}</th><th>{{ __('admin.users.projects.status') }}</th><th>{{ __('admin.users.projects.actions') }}</th></tr></thead><tbody>
    @forelse($projects as $project)
        @php
            $preview = $project->files->sortByDesc('created_at')->first(fn ($file) => str_starts_with($file->kind, 'final_preview_'))
                ?? $project->files->sortByDesc('created_at')->first(fn ($file) => str_starts_with($file->kind, 'alignment_preview_'))
                ?? $project->files->sortByDesc('created_at')->first(fn ($file) => str_contains($file->kind, 'thumbnail'));
            $finalArtifact = $project->jobs->flatMap->artifacts->firstWhere('kind', 'final');
        @endphp
        <tr><td>{{ $projects->firstItem() + $loop->index }}</td><td>{{ $project->created_at->format('d.m.Y H:i') }}</td><td><strong>{{ $project->name }}</strong></td><td>@if($preview)<img class="admin-project-preview" src="{{ route('admin.users.projects.files.show', [$user, $project, $preview]) }}" alt="{{ __('admin.users.projects.preview_alt', ['name' => $project->name]) }}">@else<span class="admin-project-no-preview">{{ __('admin.users.projects.no_preview') }}</span>@endif</td><td><span class="admin-project-status is-{{ $project->status->value }}">{{ __('admin.users.projects.statuses.'.$project->status->value) }}</span></td><td><div class="admin-users-actions"><a class="admin-user-action-icon is-projects" href="{{ route('admin.users.projects.files', [$user, $project]) }}" title="{{ __('admin.users.projects.open_files') }}" aria-label="{{ __('admin.users.projects.open_files') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.5h7l2 2h9v10.5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6.5Z"/><path d="M3 9h18"/></svg></a>@if($finalArtifact && Storage::disk($finalArtifact->disk)->exists($finalArtifact->path))<a class="admin-user-action-icon" href="{{ route('admin.users.projects.artifacts.show', [$user, $project, $finalArtifact]) }}" title="{{ __('admin.users.projects.download_final') }}" aria-label="{{ __('admin.users.projects.download_final') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 19h14"/></svg></a>@endif</div></td></tr>
    @empty<tr><td colspan="6" class="admin-users-empty">{{ __('admin.users.projects.empty') }}</td></tr>@endforelse
    </tbody></table></div>
    <div class="admin-users-pagination">{{ $projects->links() }}</div>
@endsection
