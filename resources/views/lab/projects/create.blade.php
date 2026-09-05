@extends('layouts.app')
@section('title', __('lenticular_projects.new') . ' — ' . __('site.title'))
@push('head')
    @vite(['resources/css/lab.css', 'resources/css/lenticular-lab.css', 'resources/js/lenticular-project.js'])
@endpush
@section('content')
<section class="lab-workspace-page lenticular-page"><div class="container"><div class="lenticular-panel">
    <div class="lenticular-panel-heading"><div><span class="lab-kicker">PRO / VIDEO</span><h1>{{ __('lenticular_projects.step_1') }}</h1><p>{{ __('lenticular_projects.print_intro') }}</p></div></div>
    <form method="post" action="{{ route('lab.projects.store', ['locale' => app()->getLocale()]) }}" class="lenticular-controls lenticular-setup-form" data-project-setup>@csrf
        <div class="lab-control lenticular-project-name"><label for="project-name">{{ __('lenticular_projects.name') }}</label><input id="project-name" name="name" value="{{ old('name') }}" required maxlength="50">@error('name')<small>{{ $message }}</small>@enderror</div>
        <div class="lenticular-print-options-grid"><fieldset><legend>{{ __('lenticular_projects.print_size') }}</legend><div class="lenticular-choice-row">@foreach(['A3', 'A4', 'A5', '15x10'] as $size)<label><input type="radio" name="print_size" value="{{ $size }}" @checked(old('print_size', 'A4') === $size)> {{ $size }}</label>@endforeach</div></fieldset><div class="lab-control"><label for="printer-dpi">{{ __('lenticular_projects.printer_dpi') }}</label><select id="printer-dpi" name="printer_dpi" required>@foreach($printerDpis as $dpi)<option value="{{ $dpi }}" @selected((int) old('printer_dpi', 1200) === $dpi)>{{ $dpi }} DPI</option>@endforeach</select></div></div>
        <fieldset><legend>{{ __('lenticular_projects.lens_lpi') }}</legend><div class="lenticular-choice-row">@foreach([50, 60, 75] as $lpi)<label><input type="radio" name="lpi" value="{{ $lpi }}" @checked((int) old('lpi', 60) === $lpi)> {{ $lpi }} LPI</label>@endforeach</div></fieldset>
        <p class="lenticular-frame-calculation">{{ __('lenticular_projects.flip_frame_limit') }} <strong>2–6</strong></p>
        <button class="lab-primary-button" type="submit">{{ __('lenticular_projects.next_step') }}</button>
    </form>
</div></div></section>
@endsection
