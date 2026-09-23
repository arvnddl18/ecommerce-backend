<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="format-detection" content="telephone=no" />
    <title>New Dispatch Request: Order #{{ $order->order_number }} ({{ $seller->store_name }})</title>
    <style type="text/css">
        body { margin: 0; padding: 0; min-width: 100%; background-color: #FAFAF8; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #1A1A1A; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        td { padding: 0; }
        .wrapper { width: 100% !important; background-color: #FAFAF8; padding: 48px 0; }
        .container-table { width: 620px; max-width: 620px; background-color: #FFFFFF; border: 1px solid #E6E4DE; border-radius: 4px; overflow: hidden; margin: 0 auto; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03); }
        .body-cell { padding: 44px 40px 36px 40px; }
        .masthead-title { font-size: 24px; font-weight: 800; letter-spacing: -0.05em; color: #1A1A1A; text-decoration: none; }
        .masthead-accent { color: #FF5A36; }
        .masthead-subtitle { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #8C827A; margin-top: 4px; }
        .status-pill { display: inline-block; padding: 6px 14px; background-color: #FFF2ED; border: 1px solid #FFD5C7; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #D9381E; }
        .divider-line { height: 1px; background-color: #EFECE6; margin: 28px 0; }
        .salutation-headline { font-size: 22px; font-weight: 700; letter-spacing: -0.03em; color: #1A1A1A; margin: 0 0 12px 0; }
        .editorial-lead { font-size: 15px; line-height: 1.65; color: #4A4742; margin: 0 0 24px 0; }
        .docket-table { width: 100%; background-color: #FAFAF8; border: 1px solid #EBE7DE; border-radius: 4px; margin-bottom: 32px; }
        .docket-cell { padding: 14px 18px; width: 33.33%; vertical-align: top; }
        .docket-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #8C827A; margin-bottom: 4px; }
        .docket-value { font-size: 13px; font-weight: 700; color: #1A1A1A; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .section-heading { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #1A1A1A; padding-bottom: 12px; border-bottom: 2px solid #1A1A1A; }
        .garment-row td { padding: 16px 0; border-bottom: 1px solid #F0EFEA; vertical-align: top; }
        .garment-title { font-size: 15px; font-weight: 700; color: #1A1A1A; }
        .garment-specs { font-size: 12px; color: #78716C; margin-top: 4px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .garment-qty { font-size: 13px; font-weight: 600; color: #55524E; text-align: center; }
        .garment-price { font-size: 14px; font-weight: 700; color: #1A1A1A; text-align: right; }
        .payout-card { background-color: #F8FBF9; border: 1px solid #D6EADF; border-left: 4px solid #2B7A4B; border-radius: 4px; padding: 18px 20px; margin: 28px 0; }
        .payout-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #2B7A4B; margin-bottom: 6px; }
        .payout-amount { font-size: 22px; font-weight: 800; color: #1A1A1A; }
        .payout-note { font-size: 12px; color: #55524E; margin-top: 6px; line-height: 1.5; }
        .instructions-card { background-color: #FAFAF8; border: 1px solid #EBE7DE; border-radius: 4px; padding: 18px 20px; margin: 24px 0; font-size: 13px; line-height: 1.6; color: #55524E; }
        .cta-container { text-align: center; margin: 32px 0 20px 0; }
        .cta-button { display: inline-block; background-color: #1A1A1A; color: #FFFFFF !important; text-decoration: none; padding: 15px 36px; font-size: 13px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; border-radius: 2px; }
        .footer-cell { background-color: #F7F6F2; padding: 28px 40px; border-top: 1px solid #E6E4DE; font-size: 12px; color: #8C827A; line-height: 1.6; }
        @media only screen and (max-width: 640px) {
            .container-table { width: 100% !important; border-left: 0 !important; border-right: 0 !important; }
            .body-cell { padding: 28px 20px !important; }
            .footer-cell { padding: 24px 20px !important; }
            .docket-cell { display: block !important; width: 100% !important; padding: 10px 14px !important; border-bottom: 1px solid #EFECE6 !important; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <table class="container-table" align="center" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="body-cell">
                <!-- Masthead -->
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td valign="middle">
                            <span class="masthead-title">FOLD &middot; ATELIER STUDIO</span>
                            <div class="masthead-subtitle">Maker Fulfillment Alert &middot; {{ $seller->store_name }}</div>
                        </td>
                        <td align="right" valign="middle">
                            <span class="status-pill">
                                &#9679; Dispatch Required
                            </span>
                        </td>
                    </tr>
                </table>

                <div class="divider-line"></div>

                <!-- Headline -->
                <h1 class="salutation-headline">
                    A client has ordered cuts from your studio.
                </h1>
                <p class="editorial-lead">
                    Order <strong>#{{ $order->order_number }}</strong> has successfully passed through checkout. Payment has been secured via Stripe, and your atelier studio allocation has been credited. Please review the cutting specifications below and prepare pieces for fulfillment.
                </p>

                <!-- Docket Summary -->
                <table class="docket-table" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="docket-cell">
                            <div class="docket-label">Order Reference</div>
                            <div class="docket-value">#{{ $order->order_number }}</div>
                        </td>
                        <td class="docket-cell" style="border-left: 1px solid #EBE7DE; border-right: 1px solid #EBE7DE;">
                            <div class="docket-label">Commission Date</div>
                            <div class="docket-value">
                                {{ $order->created_at ? $order->created_at->format('M d, Y · H:i') : now()->format('M d, Y · H:i') }}
                            </div>
                        </td>
                        <td class="docket-cell">
                            <div class="docket-label">Your Payout</div>
                            <div class="docket-value" style="color: #2B7A4B;">
                                &#8369;{{ number_format($netPayout / 100, 2) }}
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Items Section Heading -->
                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 8px;">
                    <tr>
                        <td class="section-heading">
                            Pieces to Prepare &amp; Dispatch
                        </td>
                    </tr>
                </table>

                <!-- Items Table -->
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <thead>
                        <tr style="border-bottom: 1px solid #EFECE6;">
                            <th align="left" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #8C827A; padding: 10px 0;">Garment</th>
                            <th align="center" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #8C827A; padding: 10px 0; width: 60px;">Qty</th>
                            <th align="right" style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #8C827A; padding: 10px 0; width: 110px;">Gross</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr class="garment-row">
                                <td>
                                    <div class="garment-title">{{ $item->product_name }}</div>
                                    @if ($item->variant_details)
                                        <div class="garment-specs">
                                            Size: <strong>{{ $item->variant_details['size'] ?? 'Standard' }}</strong> &middot; 
                                            Color: <strong>{{ $item->variant_details['color'] ?? 'Standard' }}</strong>
                                            @if (! empty($item->variant_details['sku']))
                                                &middot; SKU: {{ $item->variant_details['sku'] }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="garment-qty">
                                    {{ $item->quantity }}
                                </td>
                                <td class="garment-price">
                                    &#8369;{{ number_format($item->total_price / 100, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Payout Box -->
                <div class="payout-card">
                    <div class="payout-title">Stripe Connect Payout Allocation</div>
                    <div class="payout-amount">&#8369;{{ number_format($netPayout / 100, 2) }}</div>
                    <div class="payout-note">
                        Platform Commission: 10% retained &middot; Remaining 90% allocated directly to your Stripe Connect account.
                    </div>
                </div>

                <!-- Maker Dispatch Instructions -->
                <div class="instructions-card">
                    <strong style="color: #1A1A1A;">Maker Dispatch Checklist:</strong>
                    <ol style="margin: 8px 0 0 0; padding-left: 20px;">
                        <li>Verify sizing and specifications on the cutting table.</li>
                        <li>Affix signature atelier garment tags and inspect stitching.</li>
                        <li>Package with protective fold wrapping.</li>
                        <li>Mark as <em>Dispatched</em> and enter tracking details in your Seller Studio.</li>
                    </ol>
                </div>

                <!-- Call to action -->
                <div class="cta-container">
                    @php
                        $sellerUrl = rtrim(config('app.url', 'http://localhost:8000'), '/') . '/seller';
                    @endphp
                    <a href="{{ $sellerUrl }}" class="cta-button" target="_blank">
                        Open Seller Studio Dispatch Queue &rarr;
                    </a>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td class="footer-cell">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td valign="top">
                            <strong style="color: #1A1A1A;">FOLD Atelier Studio Creator Portal</strong><br />
                            This notification was dispatched automatically for Order #{{ $order->order_number }}.
                        </td>
                        <td align="right" valign="top">
                            <span style="font-family: monospace; font-size: 11px;">STUDIO #{{ $seller->id }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
