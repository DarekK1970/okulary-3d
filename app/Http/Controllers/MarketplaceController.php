<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceCategory;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function index(): View
    {
        return view('marketplace.index', [
            'categories' => MarketplaceCategory::query()
                ->where('is_active', true)
                ->whereHas('products', fn ($query) => $query->where('is_active', true))
                ->with(['products' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
