<?php

namespace App\Http\Controllers;

use App\Services\PortalAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsEventController extends Controller
{
    public function store(
        Request $request,
        PortalAnalyticsService $analytics
    ): JsonResponse {
        $validated =
            $request->validate([
                'event_name' => [
                    'required',
                    'string',
                    'max:80',
                    'regex:/^[a-z0-9_.-]+$/',
                ],
                'category' => [
                    'nullable',
                    'string',
                    'max:80',
                ],
                'label' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'value' => [
                    'nullable',
                    'numeric',
                    'min:-999999999',
                    'max:999999999',
                ],
                'route_name' => [
                    'nullable',
                    'string',
                    'max:160',
                ],
                'path' => [
                    'nullable',
                    'string',
                    'max:500',
                    'starts_with:/',
                ],
                'locale' => [
                    'nullable',
                    'string',
                    'max:5',
                ],
                'metadata' => [
                    'nullable',
                    'array',
                    'max:12',
                ],
            ]);

        $analytics->trackEvent(
            $request,
            $validated
        );

        return response()->json([
            'ok' => true,
        ]);
    }
}
