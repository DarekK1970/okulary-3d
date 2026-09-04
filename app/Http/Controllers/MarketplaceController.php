<?php

namespace App\Http\Controllers;

use App\Models\LenticularProject;
use App\Models\MarketplaceCategory;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function index(): View
    {
        $projectsByPrintSize = collect();
        if (auth()->check()) {
            $projectsByPrintSize = LenticularProject::query()
                ->where('user_id', auth()->id())
                ->latest('updated_at')
                ->get(['id', 'settings'])
                ->filter(fn (LenticularProject $project): bool => filled($project->settings['print_size'] ?? null))
                ->unique(fn (LenticularProject $project): string => (string) $project->settings['print_size'])
                ->keyBy(fn (LenticularProject $project): string => (string) $project->settings['print_size']);
        }

        return view('marketplace.index', [
            'categories' => MarketplaceCategory::query()
                ->with('translations')
                ->where('is_active', true)
                ->whereHas('products', fn ($query) => $query->where('is_active', true))
                ->with(['products' => fn ($query) => $query->with('translations')->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'projectsByPrintSize' => $projectsByPrintSize,
        ]);
    }
}
