<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PortalAnalyticsReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(
        Request $request,
        PortalAnalyticsReportService $reports
    ): View {
        $validated =
            $request->validate([
                'range' => [
                    'nullable',
                    Rule::in([
                        'today',
                        '7',
                        '30',
                    ]),
                ],
            ]);

        $range =
            $validated['range']
            ?? '7';

        return view(
            'admin.analytics.index',
            $reports->report(
                $range
            )
        );
    }
}
