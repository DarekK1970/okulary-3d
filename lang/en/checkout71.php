<?php

return [
    'cart' => [
        'choose_in_checkout' => 'choose at checkout',
        'shipping_note' => 'Choose the shipping method and cost in the next step.',
    ],

    'payment_statuses' => [
        'unpaid' => 'Unpaid',
        'pending' => 'Payment pending',
        'paid' => 'Paid',
        'failed' => 'Payment failed',
    ],

    'payment_methods' => [
        'bank_transfer' => 'Bank transfer',
        'paynow' => 'PayNow',
    ],

    'validation' => [
        'shipping_unavailable' => 'The selected shipping method is unavailable for this currency.',
        'payment_unavailable' => 'The selected payment method is currently unavailable.',
        'shipping_point_required' => 'Enter a parcel locker or pickup point for this shipping method.',
    ],

    'checkout' => [
        'description' => 'Choose shipping and payment, then provide the details required to fulfil the order.',
        'shipping_method' => 'Shipping method',
        'shipping_point' => 'Parcel locker / pickup point',
        'shipping_point_placeholder' => 'e.g. locker ID or pickup point name',
        'payment_method' => 'Payment method',
        'paynow_hint' => 'BLIK, instant bank transfer or card — redirect to PayNow.',
        'bank_hint' => 'Bank transfer details are shown after the order is placed.',
        'final_note' => 'The final amount includes products and the selected shipping method.',
    ],

    'success' => [
        'description' => 'Your order has been saved. Payment status, shipping and the order document are shown below.',
        'payment' => 'Payment',
        'bank_transfer_title' => 'Bank transfer details',
        'bank_transfer_description' => 'Use the order number as the payment reference.',
        'recipient' => 'Recipient',
        'bank' => 'Bank',
        'account' => 'Account number',
        'transfer_title' => 'Payment reference',
        'retry_paynow' => 'Retry PayNow payment',
        'print_document' => 'Print confirmation',
    ],

    'paynow' => [
        'not_configured' => 'PayNow is not configured.',
        'currency_not_supported' => 'PayNow is currently enabled for PLN payments in this shop.',
        'invalid_response' => 'PayNow returned an incomplete response.',
        'start_failed' => 'PayNow payment could not be started. The order was saved and payment can be retried.',
    ],

    'admin' => [
        'orders_description' => 'Order register with payment, shipping and fulfilment statuses.',
        'payment' => 'Payment',
        'all_payments' => 'All payments',
        'paid_at' => 'Paid at',
        'mark_paid' => 'Mark bank transfer as paid',
        'mark_unpaid' => 'Revert payment mark',
        'payment_updated' => 'Payment status updated.',
        'manual_only_bank_transfer' => 'Manual payment changes are available only for bank transfer.',
        'cannot_revert_after_processing' => 'Payment cannot be reverted after order processing has started.',
        'payment_required_for_processing' => 'The order must be paid before processing can start.',
        'cannot_cancel_paid_order' => 'A paid order cannot be cancelled without a payment refund workflow.',
        'cannot_cancel_pending_online' => 'An order cannot be cancelled while an online payment is pending.',
        'print_document' => 'Print confirmation',
        'point' => 'Pickup point',
    ],

    'document' => [
        'title' => 'Order confirmation',
        'number' => 'Document number',
        'order' => 'Order',
        'date' => 'Issue date',
        'buyer' => 'Buyer',
        'items' => 'Items',
        'item' => 'Product',
        'quantity' => 'Quantity',
        'unit_price' => 'Gross price',
        'value' => 'Gross value',
        'total' => 'Total',
        'print' => 'Print',
        'notice' => 'This document confirms the order and is not a VAT invoice or fiscal receipt.',
    ],

    'mail' => [
        'placed_subject' => 'Order :number received',
        'paid_subject' => 'Payment confirmed — :number',
        'shipped_subject' => 'Order :number shipped',
        'placed_heading' => 'Thank you for your order',
        'placed_intro' => 'We received order :number.',
        'paid_heading' => 'Payment confirmed',
        'paid_intro' => 'Payment for order :number has been registered.',
        'shipped_heading' => 'Your order has been shipped',
        'shipped_intro' => 'Order :number is now marked as shipped.',
        'total' => 'Total',
        'payment' => 'Payment',
        'shipping' => 'Shipping',
        'open_order' => 'Open order details',
    ],
];
