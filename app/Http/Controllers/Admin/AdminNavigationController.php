<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class AdminNavigationController extends Controller
{
    public function content(): RedirectResponse
    {
        return redirect()->route(
            'admin.articles.index'
        );
    }

    public function shop(): RedirectResponse
    {
        return redirect()->route(
            'admin.products.index'
        );
    }
}
