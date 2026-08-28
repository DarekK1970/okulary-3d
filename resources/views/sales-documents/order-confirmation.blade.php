<!DOCTYPE html>
<html lang="{{ $order->locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->number }}</title>

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f3f5f7;
            color: #1f2937;
            font-family: Arial, sans-serif;
        }
        .document {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 20mm;
            background: #fff;
            box-shadow: 0 10px 35px rgba(0,0,0,.08);
        }
        .document-top {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            border-bottom: 2px solid #111827;
            padding-bottom: 18px;
        }
        h1 { margin: 0; font-size: 24px; }
        h2 { margin: 28px 0 10px; font-size: 15px; }
        .muted { color: #6b7280; font-size: 12px; }
        .seller, .buyer {
            white-space: pre-line;
            font-size: 12px;
            line-height: 1.55;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 12px;
        }
        th, td {
            padding: 9px 7px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        th { background: #f8fafc; }
        .right { text-align: right; }
        .totals {
            width: 55%;
            margin: 22px 0 0 auto;
        }
        .totals div {
            display: flex;
            justify-content: space-between;
            padding: 7px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
        }
        .totals .grand {
            font-size: 15px;
            font-weight: 700;
            border-bottom: 0;
        }
        .notice {
            margin-top: 35px;
            padding: 12px;
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            line-height: 1.5;
        }
        .print-button {
            position: fixed;
            right: 20px;
            top: 20px;
            padding: 10px 16px;
            border: 0;
            border-radius: 7px;
            background: #0aa7df;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
        }
        @media print {
            body { background: #fff; }
            .document {
                margin: 0;
                box-shadow: none;
            }
            .print-button { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">
        {{ __('checkout71.document.print') }}
    </button>

    <main class="document">
        <div class="document-top">
            <div>
                <h1>{{ __('checkout71.document.title') }}</h1>
                <p class="muted">
                    {{ __('checkout71.document.number') }}:
                    <strong>{{ $document->number }}</strong><br>
                    {{ __('checkout71.document.order') }}:
                    {{ $order->number }}<br>
                    {{ __('checkout71.document.date') }}:
                    {{ $document->issued_at->format('d.m.Y H:i') }}
                </p>
            </div>

            <div class="seller">
                <strong>{{ $seller['name'] ?? 'Wortal Okulary 3D' }}</strong>
                {{ $seller['address'] ?? '' }}
                @if (! empty($seller['tax_id']))
NIP: {{ $seller['tax_id'] }}
                @endif
                {{ $seller['email'] ?? '' }}
            </div>
        </div>

        <h2>{{ __('checkout71.document.buyer') }}</h2>

        <div class="buyer">
            <strong>{{ $document->billing_company ?: $document->buyer_name }}</strong>
            @if ($document->billing_tax_id)
NIP: {{ $document->billing_tax_id }}
            @endif
{{ $document->billing_address }}
{{ $document->buyer_email }}
        </div>

        <h2>{{ __('checkout71.document.items') }}</h2>

        <table>
            <thead>
                <tr>
                    <th>{{ __('checkout71.document.item') }}</th>
                    <th>SKU</th>
                    <th class="right">{{ __('checkout71.document.quantity') }}</th>
                    <th class="right">{{ __('checkout71.document.unit_price') }}</th>
                    <th class="right">VAT</th>
                    <th class="right">{{ __('checkout71.document.value') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name_snapshot }}
                            @if ($item->variant_name_snapshot)
                                — {{ $item->variant_name_snapshot }}
                            @endif
                        </td>
                        <td>{{ $item->sku_snapshot }}</td>
                        <td class="right">{{ $item->quantity }}</td>
                        <td class="right">{{ number_format((float) $item->unit_price_gross, 2, ',', ' ') }} {{ $item->currency }}</td>
                        <td class="right">{{ number_format((float) $item->vat_rate, 2, ',', ' ') }}%</td>
                        <td class="right">{{ number_format((float) $item->line_total_gross, 2, ',', ' ') }} {{ $item->currency }}</td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="5">{{ $order->shipping_name_snapshot }}</td>
                    <td class="right">{{ number_format((float) $order->shipping_gross, 2, ',', ' ') }} {{ $order->currency }}</td>
                </tr>
            </tbody>
        </table>

        <div class="totals">
            <div>
                <span>{{ __('cart.summary.products') }}</span>
                <strong>{{ number_format((float) $document->subtotal_gross, 2, ',', ' ') }} {{ $document->currency }}</strong>
            </div>
            <div>
                <span>{{ __('cart.summary.shipping') }}</span>
                <strong>{{ number_format((float) $document->shipping_gross, 2, ',', ' ') }} {{ $document->currency }}</strong>
            </div>
            <div class="grand">
                <span>{{ __('checkout71.document.total') }}</span>
                <strong>{{ number_format((float) $document->total_gross, 2, ',', ' ') }} {{ $document->currency }}</strong>
            </div>
        </div>

        <div class="notice">
            {{ __('checkout71.document.notice') }}
        </div>
    </main>
</body>
</html>
