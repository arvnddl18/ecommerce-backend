<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation #{{ $order->order_number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #FAFAF8; color: #1A1A1A; margin: 0; padding: 24px; }
        .container { max-width: 600px; margin: 0 auto; background: #FFFFFF; border: 1px solid #E8E6E1; padding: 32px; }
        .header { border-bottom: 2px solid #1A1A1A; padding-bottom: 16px; margin-bottom: 24px; }
        .wordmark { font-size: 24px; font-weight: 700; letter-spacing: -0.05em; color: #1A1A1A; }
        .accent { color: #FF5A36; }
        .item-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #F0EFEA; }
        .total-box { margin-top: 24px; padding-top: 16px; border-top: 2px solid #1A1A1A; font-weight: bold; font-size: 18px; text-align: right; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <span class="wordmark">FOLD<span class="accent">.</span></span>
        <p style="color: #6B6B6B; font-size: 13px; margin-top: 4px;">Tactile Wardrobe · Order Confirmation</p>
    </div>

    <h2>Thank you for your order, {{ $order->customer_name ?: 'Collector' }}.</h2>
    <p>Your order <strong>#{{ $order->order_number }}</strong> has been verified and sent to our atelier makers for fulfillment.</p>

    <h3 style="margin-top: 24px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em;">Purchased Cuts</h3>
    @foreach ($order->items as $item)
        <div class="item-row">
            <div>
                <strong>{{ $item->product_name }}</strong> x {{ $item->quantity }}
                @if ($item->variant_details)
                    <div style="font-size: 12px; color: #6B6B6B;">
                        {{ $item->variant_details['size'] ?? '' }} · {{ $item->variant_details['color'] ?? '' }}
                    </div>
                @endif
            </div>
            <div>₱{{ number_format($item->total_price / 100, 2) }}</div>
        </div>
    @endforeach

    <div class="total-box">
        Total: ₱{{ number_format($order->total_amount / 100, 2) }}
    </div>

    <p style="margin-top: 32px; font-size: 12px; color: #8C827A;">
        You can track dispatch progress in your account at any time.
    </p>
</div>
</body>
</html>
