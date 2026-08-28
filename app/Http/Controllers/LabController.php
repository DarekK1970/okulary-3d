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

    public function lenticular(string $locale): View
    {
        return view('lab.lenticular');
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
