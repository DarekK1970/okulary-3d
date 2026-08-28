<!DOCTYPE html>
<html lang="{{ $order->locale }}">
<body style="font-family:Arial,sans-serif;color:#25324a;line-height:1.6">
    <h2>{{ __('checkout71.mail.shipped_heading') }}</h2>
    <p>{{ __('checkout71.mail.shipped_intro', ['number' => $order->number]) }}</p>
    <p>
        <strong>{{ __('checkout71.mail.shipping') }}:</strong>
        {{ $order->shipping_name_snapshot }}
    </p>
    <p>
        <a href="{{ route('order.success', [
            'locale' => $order->locale,
            'order' => $order->public_token
        ]) }}">
            {{ __('checkout71.mail.open_order') }}
        </a>
    </p>
</body>
</html>
