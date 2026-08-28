<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SalesDocument;

class SalesDocumentService
{
    public function createOrderConfirmation(
        Order $order
    ): SalesDocument {
        $existing = $order->salesDocuments()
            ->where('type', SalesDocument::TYPE_ORDER_CONFIRMATION)
            ->first();

        if ($existing) {
            return $existing;
        }

        return SalesDocument::create([
            'order_id' => $order->id,
            'type' => SalesDocument::TYPE_ORDER_CONFIRMATION,
            'number' => $this->documentNumber($order),
            'currency' => $order->currency,
            'subtotal_gross' => $order->subtotal_gross,
            'shipping_gross' => $order->shipping_gross,
            'total_gross' => $order->total_gross,
            'buyer_name' => $order->customerName(),
            'buyer_email' => $order->customer_email,
            'billing_company' => $order->billing_company,
            'billing_tax_id' => $order->billing_tax_id,
            'billing_address' => $this->billingAddress($order),
            'issued_at' => now(),
        ]);
    }

    private function documentNumber(Order $order): string
    {
        return sprintf(
            'PZ/%s/%06d',
            $order->placed_at?->format('Y') ?? now()->format('Y'),
            $order->id
        );
    }

    private function billingAddress(Order $order): string
    {
        $parts = array_filter([
            $order->billing_address_line1,
            $order->billing_address_line2,
            trim(
                $order->billing_postal_code
                . ' '
                . $order->billing_city
            ),
            $order->billing_country_code,
        ]);

        return implode("\n", $parts);
    }
}
