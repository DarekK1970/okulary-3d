@extends('layouts.app')

@section('title', __('lenticular_projects.new') . ' — ' . __('site.title'))

@push('head')
    @vite(['resources/css/lab.css', 'resources/css/lenticular-lab.css'])
@endpush

@section('content')
<section class="lab-workspace-page lenticular-page">
    <div class="container">
        <div class="lenticular-panel">
            <div class="lenticular-panel-heading"><div><span class="lab-kicker">PRO / VIDEO</span><h1>{{ __('lenticular_projects.new') }}</h1><p>{{ __('lenticular_projects.description') }}</p></div></div>
            <form method="post" action="{{ route('lab.projects.store', ['locale' => app()->getLocale()]) }}" enctype="multipart/form-data" class="lenticular-controls">
                @csrf
                <div class="lab-control"><label for="project-name">{{ __('lenticular_projects.name') }}</label><input id="project-name" name="name" value="{{ old('name') }}" required maxlength="150">@error('name')<small>{{ $message }}</small>@enderror</div>
                <div class="lab-control"><label for="project-video">{{ __('lenticular_projects.video') }}</label><input id="project-video" name="video" type="file" accept="video/mp4,video/quicktime,video/webm" required>@error('video')<small>{{ $message }}</small>@enderror</div>
                <button class="lab-primary-button" type="submit">{{ __('lenticular_projects.upload') }}</button>
            </form>
        </div>
    </div>
</section>
@endsection
