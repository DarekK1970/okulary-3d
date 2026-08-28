<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PlaceholderController extends Controller
{
    public function show(string $section): View
    {
        abort_unless(array_key_exists($section, __('admin.sections')), 404);

        return view('admin.placeholder', [
            'section' => $section,
        ]);
    }
}
