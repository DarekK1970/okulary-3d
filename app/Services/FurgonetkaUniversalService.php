<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class FurgonetkaUniversalService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function orders(
        ?string $datetime,
        int $limit = 30
    ): Collection {
        $limit = max(
            1,
            min(100, $limit)
        );

        $query = Order::query()
            ->with('items')
            ->whereNotNull(
                'placed_at'
            )
            ->where(
                'shipping_method',
                '!=',
                'pickup'
            )
            ->orderBy(
                'updated_at'
            )
            ->orderBy('id');

        if (filled($datetime)) {
            try {
                $changedSince =
                    Carbon::parse(
                        $datetime
                    );
            } catch (\Throwable) {
                throw new InvalidArgumentException(
                    'Invalid datetime parameter.'
                );
            }

            /*
             * >= is intentional.
             * The Universal API has a datetime cursor but no
             * secondary cursor. Repeating the boundary record
             * is safer than skipping two orders sharing the
             * same timestamp.
             */
            $query->where(
                'updated_at',
                '>=',
                $changedSince
            );
        }

        return $query
            ->limit($limit)
            ->get()
            ->map(
                fn (Order $order): array =>
                    $this->serializeOrder(
                        $order
                    )
            );
    }

    public function applyTracking(
        Order $order,
        array $tracking
    ): void {
        $number = trim(
            (string) (
                $tracking['number']
                ?? ''
            )
        );

        if ($number === '') {
            throw new InvalidArgumentException(
                'Tracking number is required.'
            );
        }

        $carrier = $this
            ->nullableString(
                $tracking['carrier']
                ?? $tracking['service']
                ?? null
            );

        $url = $this
            ->nullableString(
                $tracking['url']
                ?? $tracking[
                    'tracking_url'
                ]
                ?? null
            );

        $externalId = $this
            ->nullableString(
                $tracking['id']
                ?? $tracking[
                    'shipment_id'
                ]
                ?? null
            );

        $sameSnapshot =
            $order
                ->shipping_tracking_number
                === $number
            && (
                $carrier === null
                || $order
                    ->shipping_carrier
                    === $carrier
            )
            && (
                $url === null
                || $order
                    ->shipping_tracking_url
                    === $url
            )
            && (
                $externalId === null
                || $order
                    ->shipping_external_id
                    === $externalId
            );

        if ($sameSnapshot) {
            return;
        }

        $values = [
            'shipping_tracking_number' =>
                $number,
            'shipping_tracking_updated_at' =>
                now(),
        ];

        if ($carrier !== null) {
            $values[
                'shipping_carrier'
            ] = $carrier;
        }

        if ($url !== null) {
            $values[
                'shipping_tracking_url'
            ] = $url;
        }

        if ($externalId !== null) {
            $values[
                'shipping_external_id'
            ] = $externalId;
        }

        $order->update($values);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOrder(
        Order $order
    ): array {
        $totalBase = $this
            ->money(
                $order
                    ->total_base_gross
                ?? $order
                    ->total_gross
            );

        $shippingBase =
            $this->money(
                $order
                    ->shipping_base_gross
                ?? $order
                    ->shipping_gross
            );

        $paidBase =
            $order->payment_status
                === PaymentStatus::Paid
            ? $totalBase
            : 0.0;

        return [
            'sourceOrderId' =>
                $order->number,

            'sourceClientId' =>
                $order->user_id
                    ? (string) $order
                        ->user_id
                    : null,

            'datetimeOrder' =>
                (
                    $order->placed_at
                    ?? $order->created_at
                )
                    ?->toIso8601String(),

            'sourceDatetimeChange' =>
                $order->updated_at
                    ?->toIso8601String(),

            /*
             * Universal integration imports the order into
             * Furgonetka. The actual carrier/service is chosen
             * there, therefore service remains null.
             */
            'service' => null,

            'serviceDescription' =>
                $order
                    ->shipping_name_snapshot
                ?: $order
                    ->shipping_method,

            'status' =>
                $this->statusName(
                    $order->status
                ),

            /*
             * Universal example has no currency field.
             * We therefore export canonical PLN snapshots,
             * never foreign display amounts.
             */
            'totalPrice' =>
                $totalBase,

            'shippingCost' =>
                $shippingBase,

            'totalPaid' =>
                $paidBase,

            'codAmount' =>
                0,

            'totalWeight' =>
                $order
                    ->shipping_weight_grams
                ? round(
                    (
                        (int) $order
                            ->shipping_weight_grams
                    ) / 1000,
                    3
                )
                : null,

            'point' =>
                $order
                    ->shipping_point
                ?: null,

            'comment' =>
                $order
                    ->customer_note
                ?: null,

            'shippingAddress' => [
                'company' =>
                    $order
                        ->shipping_company
                    ?: null,

                'name' => trim(
                    $order
                        ->shipping_first_name
                    . ' '
                    . $order
                        ->shipping_last_name
                ),

                'street' =>
                    $this
                        ->shippingStreet(
                            $order
                        ),

                'city' =>
                    $order
                        ->shipping_city,

                'postcode' =>
                    $order
                        ->shipping_postal_code,

                'countryCode' =>
                    strtoupper(
                        (string) $order
                            ->shipping_country_code
                    ),

                'phone' =>
                    $order
                        ->customer_phone
                    ?: null,

                'email' =>
                    $order
                        ->customer_email,
            ],

            'products' =>
                $order->items
                    ->map(
                        fn (
                            OrderItem $item
                        ): array =>
                            $this
                                ->serializeItem(
                                    $item
                                )
                    )
                    ->values()
                    ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeItem(
        OrderItem $item
    ): array {
        $gross = $this->money(
            $item
                ->base_unit_price_gross
            ?? $item
                ->unit_price_gross
        );

        $vat = $this->money(
            $item->vat_rate
        );

        $net = $vat > 0
            ? round(
                $gross
                / (
                    1
                    + $vat / 100
                ),
                2
            )
            : $gross;

        $name = trim(
            $item
                ->product_name_snapshot
            . (
                filled(
                    $item
                        ->variant_name_snapshot
                )
                    ? ' — '
                        . $item
                            ->variant_name_snapshot
                    : ''
            )
        );

        return [
            'sourceProductId' =>
                (string) (
                    $item
                        ->product_variant_id
                    ?? $item
                        ->product_id
                    ?? $item->id
                ),

            'name' => $name,

            'priceGross' =>
                $gross,

            'priceNet' =>
                $net,

            'vat' =>
                $vat,

            /*
             * Item-level weight was not historically snapshotted.
             * The immutable order shipment weight is exported
             * as totalWeight instead.
             */
            'weight' => null,

            'quantity' =>
                (int) $item
                    ->quantity,

            'width' => null,
            'height' => null,
            'depth' => null,

            'sku' =>
                $item
                    ->sku_snapshot
                ?: null,

            'imageUrl' => null,
        ];
    }

    private function statusName(
        OrderStatus $status
    ): string {
        return match ($status) {
            OrderStatus::Pending =>
                'Nowe',
            OrderStatus::Processing =>
                'W realizacji',
            OrderStatus::Shipped =>
                'Wysłane',
            OrderStatus::Completed =>
                'Zakończone',
            OrderStatus::Cancelled =>
                'Anulowane',
        };
    }

    private function shippingStreet(
        Order $order
    ): string {
        return trim(
            $order
                ->shipping_address_line1
            . (
                filled(
                    $order
                        ->shipping_address_line2
                )
                    ? ', '
                        . $order
                            ->shipping_address_line2
                    : ''
            )
        );
    }

    private function money(
        mixed $value
    ): float {
        return round(
            (float) ($value ?? 0),
            2
        );
    }

    private function nullableString(
        mixed $value
    ): ?string {
        if (
            ! is_scalar($value)
        ) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        return $value === ''
            ? null
            : $value;
    }
}
