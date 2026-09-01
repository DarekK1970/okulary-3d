<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\FurgonetkaUniversalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class FurgonetkaUniversalController extends Controller
{
    public function orders(
        Request $request,
        FurgonetkaUniversalService $service
    ): JsonResponse {
        $validated = $request->validate([
            'datetime' => [
                'nullable',
                'string',
                'max:80',
            ],
            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        try {
            $orders = $service->orders(
                $validated['datetime']
                    ?? null,
                (int) (
                    $validated['limit']
                    ?? 30
                )
            );
        } catch (
            InvalidArgumentException
            $exception
        ) {
            return response()->json(
                [
                    'message' =>
                        $exception
                            ->getMessage(),
                ],
                422
            );
        }

        return response()->json([
            'data' =>
                $orders->values(),
        ]);
    }

    public function tracking(
        Request $request,
        string $id,
        FurgonetkaUniversalService $service
    ): JsonResponse {
        $validated = $request->validate([
            'tracking.number' => [
                'required',
                'string',
                'max:190',
            ],

            /*
             * The documented example requires tracking.number.
             * Optional fields are accepted when Furgonetka
             * supplies them, without making them mandatory.
             */
            'tracking.carrier' => [
                'nullable',
                'string',
                'max:100',
            ],
            'tracking.service' => [
                'nullable',
                'string',
                'max:100',
            ],
            'tracking.url' => [
                'nullable',
                'url',
                'max:2000',
            ],
            'tracking.tracking_url' => [
                'nullable',
                'url',
                'max:2000',
            ],
            'tracking.id' => [
                'nullable',
                'string',
                'max:190',
            ],
            'tracking.shipment_id' => [
                'nullable',
                'string',
                'max:190',
            ],
        ]);

        $order = Order::query()
            ->where(
                'number',
                $id
            )
            ->firstOrFail();

        $service->applyTracking(
            $order,
            $validated['tracking']
        );

        return response()->json(
            null,
            204
        );
    }
}
