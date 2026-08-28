<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountOrderController extends Controller
{
    public function index(
        Request $request,
        string $locale
    ): View {
        return view('account.orders.index', [
            'orders' => Order::query()
                ->where('user_id', $request->user()->id)
                ->withCount('items')
                ->latest('placed_at')
                ->paginate(20),
        ]);
    }

    public function show(
        Request $request,
        string $locale,
        Order $order
    ): View {
        abort_unless(
            $order->user_id === $request->user()->id,
            404
        );

        $order->load('items');

        return view('account.orders.show', [
            'order' => $order,
        ]);
    }
}
