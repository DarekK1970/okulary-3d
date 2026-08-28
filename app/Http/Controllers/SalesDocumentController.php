<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SalesDocument;
use Illuminate\View\View;

class SalesDocumentController extends Controller
{
    public function publicShow(
        string $locale,
        Order $order,
        SalesDocument $document
    ): View {
        abort_unless(
            $document->order_id === $order->id,
            404
        );

        return $this->render($order, $document);
    }

    public function adminShow(
        Order $order,
        SalesDocument $document
    ): View {
        abort_unless(
            $document->order_id === $order->id,
            404
        );

        return $this->render($order, $document);
    }

    private function render(
        Order $order,
        SalesDocument $document
    ): View {
        $order->load('items');

        return view(
            'sales-documents.order-confirmation',
            [
                'order' => $order,
                'document' => $document,
                'seller' => config('shop.seller', []),
            ]
        );
    }
}
