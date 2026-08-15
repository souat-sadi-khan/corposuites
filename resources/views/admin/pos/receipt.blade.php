<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $posSale->pos_number }}</title>
    <style>
        body { font-family: 'Courier New', monospace; color: #222; margin: 0 auto; max-width: 340px; padding: 20px; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 4px; }
        .center { text-align: center; }
        .muted { color: #666; font-size: 12px; }
        hr { border: none; border-top: 1px dashed #999; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        td { padding: 3px 0; vertical-align: top; }
        .right { text-align: right; }
        .totals-row td { font-weight: bold; }
        .print-actions { text-align: center; margin-bottom: 16px; }
        @media print {
            .print-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button onclick="window.print()">Print</button>
    </div>

    <h1>CorpoSuites</h1>
    <div class="center muted">Sale Receipt</div>
    <hr>

    <div>Receipt #: {{ $posSale->pos_number }}</div>
    <div>Date: {{ optional($posSale->sold_at)->format('d M, Y h:i A') }}</div>
    <div>Customer: {{ $posSale->customer->name ?? 'Walk-in Customer' }}</div>
    <div>Cashier: {{ $posSale->cashier->name ?? '-' }}</div>
    @if($posSale->pos_status === 'voided')
        <div class="center" style="font-weight:bold; margin-top:6px;">*** VOIDED ***</div>
    @endif
    <hr>

    <table>
        @foreach($posSale->items as $item)
            <tr>
                <td colspan="2">{{ $item->product->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>{{ $item->quantity }} x {{ number_format($item->unit_price, 2) }}
                    @if($item->discount > 0)
                        (-{{ number_format($item->discount, 2) }})
                    @endif
                </td>
                <td class="right">{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </table>
    <hr>

    <table>
        <tr>
            <td>Subtotal</td>
            <td class="right">{{ number_format($posSale->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="right">{{ number_format($posSale->discount_total, 2) }}</td>
        </tr>
        <tr class="totals-row">
            <td>Grand Total</td>
            <td class="right">{{ number_format($posSale->grand_total, 2) }}</td>
        </tr>
        <tr>
            <td>Payment ({{ ucfirst(str_replace('_', ' ', $posSale->payment_method)) }})</td>
            <td class="right">{{ number_format($posSale->amount_tendered ?? $posSale->grand_total, 2) }}</td>
        </tr>
        <tr>
            <td>Change Due</td>
            <td class="right">{{ number_format($posSale->change_due, 2) }}</td>
        </tr>
    </table>
    <hr>

    <div class="center muted">Thank you for your purchase!</div>
</body>
</html>
