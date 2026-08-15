<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Note {{ $deliveryNote->note_number }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #222; margin: 40px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .muted { color: #666; }
        .head-row { display: flex; justify-content: space-between; margin-bottom: 24px; }
        .box { border: 1px solid #ddd; padding: 12px 16px; border-radius: 6px; min-width: 260px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; font-size: 14px; }
        th { background: #f5f5f5; }
        .signatures { display: flex; justify-content: space-between; margin-top: 60px; }
        .signature-box { width: 45%; border-top: 1px solid #333; padding-top: 6px; text-align: center; }
        .print-actions { margin-bottom: 20px; }
        @media print {
            .print-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button onclick="window.print()">Print</button>
    </div>

    <div class="head-row">
        <div>
            <h1>Delivery Note</h1>
            <div class="muted">{{ $deliveryNote->note_number }}</div>
        </div>
        <div class="box">
            <div><b>Sales Order:</b> {{ $deliveryNote->delivery->salesOrder->order_number ?? '-' }}</div>
            <div><b>Delivery #:</b> {{ $deliveryNote->delivery->delivery_number ?? '-' }}</div>
            <div><b>Customer:</b> {{ $deliveryNote->delivery->salesOrder->customer->name ?? '-' }}</div>
            <div><b>Issued Date:</b> {{ optional($deliveryNote->issued_date)->format('d M, Y') }}</div>
            <div><b>Carrier:</b> {{ $deliveryNote->delivery->carrier ?? '-' }}</div>
            <div><b>Tracking #:</b> {{ $deliveryNote->delivery->tracking_number ?? '-' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>SKU</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deliveryNote->delivery->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product->name ?? '-' }}</td>
                    <td>{{ $item->product->sku ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No items on this delivery.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($deliveryNote->remarks)
        <p><b>Remarks:</b> {{ $deliveryNote->remarks }}</p>
    @endif

    <div class="signatures">
        <div class="signature-box">Delivered By</div>
        <div class="signature-box">
            Received By
            @if($deliveryNote->received_by)
                <div class="muted">{{ $deliveryNote->received_by }}{{ $deliveryNote->received_date ? ' — ' . $deliveryNote->received_date->format('d M, Y') : '' }}</div>
            @endif
        </div>
    </div>
</body>
</html>
