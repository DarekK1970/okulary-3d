<?php

namespace App\Http\Controllers;

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
}
