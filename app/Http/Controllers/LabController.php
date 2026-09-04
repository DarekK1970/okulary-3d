<?php

namespace App\Http\Controllers;

use App\Services\FalAiSettingsService;
use App\Services\LenticularAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabController extends Controller
{
    public function index(string $locale): View
    {
        return view('lab.index');
    }

    public function anaglyph(string $locale): View
    {
        return view('lab.anaglyph');
    }

    public function stereoAlignment(string $locale): View
    {
        return view('lab.stereo-alignment');
    }

    public function lenticular(string $locale): View
    {
        return view('lab.lenticular');
    }

    public function lenticularStudio(Request $request, string $locale, FalAiSettingsService $settings, LenticularAccessService $access): View
    {
        return view('lab.lenticular-studio', [
            'falReady' => $settings->configured(),
            'accessPlan' => $access->plan($request->user()),
        ]);
    }

    public function mpo(string $locale): View
    {
        return view('lab.mpo');
    }

    public function wigglegram(string $locale): View
    {
        return view('lab.wigglegram');
    }
}
