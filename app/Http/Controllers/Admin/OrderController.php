<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use App\Services\PaymentWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::query()
            ->withCount('items')
            ->latest('placed_at');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('number', 'like', '%' . $search . '%')
                    ->orWhere('customer_email', 'like', '%' . $search . '%')
                    ->orWhere('customer_first_name', 'like', '%' . $search . '%')
                    ->orWhere('customer_last_name', 'like', '%' . $search . '%');
            });
        }

        if (
            $request->filled('status')
            && in_array(
                $request->input('status'),
                OrderStatus::values(),
                true
            )
        ) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment')) {
            $query->where(
                'payment_status',
                $request->input('payment')
            );
        }

        return view('admin.orders.index', [
            'orders' => $query->paginate(30)->withQueryString(),
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'items',
            'user',
            'salesDocuments',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
        ]);
    }

    public function updateStatus(
        Request $request,
        Order $order,
        OrderWorkflowService $workflow
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(OrderStatus::values()),
            ],
        ]);

        $workflow->transition(
            $order,
            OrderStatus::from($validated['status'])
        );

        return back()->with(
            'status',
            __('cart.admin.status_updated')
        );
    }

    public function updatePayment(
        Request $request,
        Order $order,
        PaymentWorkflowService $workflow
    ): RedirectResponse {
        $validated = $request->validate([
            'payment_action' => [
                'required',
                Rule::in(['paid', 'unpaid']),
            ],
        ]);

        if ($validated['payment_action'] === 'paid') {
            $workflow->markBankTransferPaid($order);
        } else {
            $workflow->markBankTransferUnpaid($order);
        }

        return back()->with(
            'status',
            __('checkout71.admin.payment_updated')
        );
    }
}
