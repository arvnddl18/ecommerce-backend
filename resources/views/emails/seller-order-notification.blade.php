<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Dispatch Request #{{ $order->order_number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #FAFAF8; color: #1A1A1A; margin: 0; padding: 24px; }
        .container { max-width: 600px; margin: 0 auto; background: #FFFFFF; border: 1px solid #E8E6E1; padding: 32px; }
        .header { border-bottom: 2px solid #FF5A36; padding-bottom: 16px; margin-bottom: 24px; }
        .wordmark { font-size: 24px; font-weight: 700; letter-spacing: -0.05em; color: #1A1A1A; }
        .item-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #F0EFEA; }
        .payout-box { margin-top: 24px; padding: 16px; background-color: #F8F7F4; border-left: 3px solid #4A7C59; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <span class="wordmark">FOLD · ATELIER STUDIO</span>
        <p style="color: #6B6B6B; font-size: 13px; margin-top: 4px;">Maker Fulfillment Alert · {{ $seller->store_name }}</p>
    </div>

    <h2>A customer ordered cuts from your studio.</h2>
    <p>Order <strong>#{{ $order->order_number }}</strong> has been paid. Please prepare and dispatch the following garments:</p>

    <h3 style="margin-top: 24px; font-size: 14px; text-transform: uppercase;">Items to Dispatch</h3>
    @foreach ($items as $item)
        <div class="item-row">
            <div>
                <strong>{{ $item->product_name }}</strong> x {{ $item->quantity }}
                @if ($item->variant_details)
                    <div style="font-size: 12px; color: #6B6B6B;">
                        Size: {{ $item->variant_details['size'] ?? 'N/A' }} · Color: {{ $item->variant_details['color'] ?? 'N/A' }} · SKU: {{ $item->variant_details['sku'] ?? 'N/A' }}
                    </div>
                @endif
            </div>
            <div>₱{{ number_format($item->total_price / 100, 2) }}</div>
        </div>
    @endforeach

    <div class="payout-box">
        <strong>Stripe Connect Payout Allocation:</strong> ₱{{ number_format($netPayout / 100, 2) }}
        <div style="font-size: 12px; color: #6B6B6B; margin-top: 4px;">
            Platform Commission: 10% retained · Remaining 90% credited to your Stripe account.
        </div>
    </div>

    <p style="margin-top: 24px; font-size: 13px;">
        Head to your <strong>Seller Studio Dispatch Queue</strong> to mark these pieces as dispatched and update tracking status.
    </p>
</div>
</body>
</html>
