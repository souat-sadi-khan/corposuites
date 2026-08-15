@extends('admin.layout.app', ['title' => 'Low Stock Alerts'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Low Stock Alerts</h2>
            <div class="sec-sub">Products/warehouses whose current stock balance has fallen to or below their configured Reorder Level</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <select name="product_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ (string) $productId === (string) $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                @endforeach
            </select>

            <select name="warehouse_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Warehouses</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" {{ (string) $warehouseId === (string) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                @endforeach
            </select>

            @if($productId || $warehouseId)
                <a href="{{ route('admin.low-stock-alerts.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Alerts</div>
                <div class="stat-val">{{ number_format($totalAlerts) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-alarm-warning-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Products Affected</div>
                <div class="stat-val">{{ number_format($productsAffected) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-box-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Warehouses Affected</div>
                <div class="stat-val">{{ number_format($warehousesAffected) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-building-2-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Out of Stock</div>
                <div class="stat-val">{{ number_format($outOfStockCount) }}</div>
            </div>
            <div class="stat-icon-wrap si-red">
                <i class="ri-close-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Shortfall Quantity</div>
                <div class="stat-val">{{ number_format($totalShortfallQuantity, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-arrow-down-circle-line"></i>
            </div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Low Stock Products</div>
                <div class="nx-card-sub">Only product/warehouse combinations with a configured Reorder Level and a balance at or below it are shown, sorted by largest shortfall first</div>
            </div>
        </div>

        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th class="text-end">Current Balance</th>
                            <th class="text-end">Reorder Level</th>
                            <th class="text-end">Shortfall</th>
                            <th class="text-end">Suggested Reorder Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alerts as $row)
                            <tr>
                                <td>{{ $row['product'] }}</td>
                                <td>{{ $row['warehouse'] }}</td>
                                <td class="text-end {{ $row['balance'] <= 0 ? 'text-danger' : '' }}">{{ number_format($row['balance'], 2) }}</td>
                                <td class="text-end">{{ number_format($row['reorder_level'], 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($row['shortfall'], 2) }}</td>
                                <td class="text-end">{{ $row['reorder_quantity'] !== null ? number_format($row['reorder_quantity'], 2) : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No low stock alerts — all tracked stock is above its reorder level</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
