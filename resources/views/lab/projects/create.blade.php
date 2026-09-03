@extends('layouts.app')
@section('title', __('lenticular_projects.new') . ' — ' . __('site.title'))
@push('head')
    @vite(['resources/css/lab.css', 'resources/css/lenticular-lab.css', 'resources/js/lenticular-project.js'])
@endpush
@section('content')
<section class="lab-workspace-page lenticular-page"><div class="container"><div class="lenticular-panel">
    <div class="lenticular-panel-heading"><div><span class="lab-kicker">PRO / VIDEO</span><h1>{{ __('lenticular_projects.step_1') }}</h1><p>{{ __('lenticular_projects.print_intro') }}</p></div></div>
    <form method="post" action="{{ route('lab.projects.store', ['locale' => app()->getLocale()]) }}" class="lenticular-controls lenticular-setup-form" data-project-setup>@csrf
        <div class="lab-control"><label for="project-name">{{ __('lenticular_projects.name') }}</label><input id="project-name" name="name" value="{{ old('name') }}" required maxlength="150">@error('name')<small>{{ $message }}</small>@enderror</div>
        <fieldset><legend>{{ __('lenticular_projects.print_size') }}</legend><div class="lenticular-choice-row">@foreach(['A3', 'A4', 'A5', '15x10'] as $size)<label><input type="radio" name="print_size" value="{{ $size }}" @checked(old('print_size', 'A4') === $size)> {{ $size }}</label>@endforeach</div></fieldset>
        <div class="lab-control"><label for="printer-dpi">{{ __('lenticular_projects.printer_dpi') }}</label><input id="printer-dpi" name="printer_dpi" type="number" min="300" max="4800" value="{{ old('printer_dpi', 1200) }}" required></div>
        <label class="lenticular-company-print"><input name="print_service" type="checkbox" value="1" @checked(old('print_service'))> {{ __('lenticular_projects.company_print') }}</label>
        <fieldset><legend>{{ __('lenticular_projects.lens_lpi') }}</legend><div class="lenticular-choice-row">@foreach([50, 60, 75] as $lpi)<label><input type="radio" name="lpi" value="{{ $lpi }}" @checked((int) old('lpi', 60) === $lpi)> {{ $lpi }} LPI</label>@endforeach</div></fieldset>
        <p class="lenticular-frame-calculation">{{ __('lenticular_projects.available_frames') }} <strong data-max-frames>20</strong></p>
        <button class="lab-primary-button" type="submit">{{ __('lenticular_projects.next_step') }}</button>
    </form>
</div></div></section>
@endsection
