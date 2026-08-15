<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Barcode Labels</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
        }
        .label-sheet {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .label {
            border: 1px dashed #999;
            padding: 8px;
            width: 220px;
            text-align: center;
            box-sizing: border-box;
        }
        .label-name {
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .label-price {
            font-size: 12px;
            margin-top: 2px;
        }
        .print-actions {
            margin-bottom: 10px;
        }
        @media print {
            .print-actions {
                display: none;
            }
            .label {
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button onclick="window.print()">Print</button>
    </div>

    @if(empty($labels))
        <p>No labels to generate.</p>
    @else
        <div class="label-sheet">
            @foreach($labels as $index => $product)
                <div class="label">
                    <div class="label-name">{{ $product->name }}</div>
                    <svg class="barcode" data-code="{{ $product->sku }}"></svg>
                    @if($product->selling_price !== null)
                        <div class="label-price">{{ number_format($product->selling_price, 2) }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <script>
        document.querySelectorAll('.barcode').forEach(function (el) {
            JsBarcode(el, el.dataset.code, {
                format: 'CODE128',
                width: 1.5,
                height: 40,
                fontSize: 12,
                margin: 4
            });
        });
    </script>
</body>
</html>
