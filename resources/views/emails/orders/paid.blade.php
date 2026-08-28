<!DOCTYPE html>
<html lang="{{ $order->locale }}">
<body style="font-family:Arial,sans-serif;color:#25324a;line-height:1.6">
    <h2>{{ __('checkout71.mail.paid_heading') }}</h2>
    <p>{{ __('checkout71.mail.paid_intro', ['number' => $order->number]) }}</p>
    <p>
        <strong>{{ __('checkout71.mail.total') }}:</strong>
        {{ number_format((float) $order->total_gross, 2, ',', ' ') }}
        {{ $order->currency }}
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
