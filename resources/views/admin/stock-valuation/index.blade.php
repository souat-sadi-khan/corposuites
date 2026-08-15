@extends('admin.layout.app', ['title' => 'Stock Valuation'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Stock Valuation</h2>
            <div class="sec-sub">Current on-hand quantity valued at each product's weighted-average unit cost</div>
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
                <a href="{{ route('admin.stock-valuation.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Inventory Value</div>
                <div class="stat-val">{{ number_format($totalInventoryValue, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-money-dollar-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Quantity On Hand</div>
                <div class="stat-val">{{ number_format($totalQuantityOnHand, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-box-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Lines Valued</div>
                <div class="stat-val">{{ number_format($totalLinesValued) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Products Missing Cost Data</div>
                <div class="stat-val">{{ number_format($productsWithNoCostData) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-alarm-warning-line"></i>
            </div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Valuation by Product / Warehouse</div>
                <div class="nx-card-sub">Only positive on-hand balances are shown; unit cost is a weighted average across all recorded inbound costs for that product</div>
            </div>
        </div>

        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Avg. Unit Cost</th>
                            <th class="text-end">Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($valuation as $row)
                            <tr>
                                <td>{{ $row['product'] }}</td>
                                <td>{{ $row['warehouse'] }}</td>
                                <td class="text-end">{{ number_format($row['balance'], 2) }}</td>
                                <td class="text-end">{{ $row['avg_unit_cost'] !== null ? number_format($row['avg_unit_cost'], 2) : '-' }}</td>
                                <td class="text-end">{{ $row['total_value'] !== null ? number_format($row['total_value'], 2) : 'No cost data' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No stock on hand yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
