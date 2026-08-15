@extends('admin.layout.app', ['title' => 'Inventory Transactions'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Inventory Transactions</h2>
            <div class="sec-sub">Unified stock movement ledger across Opening Stock, Stock Entry, Stock Adjustment, and Stock Transfer</div>
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
                <a href="{{ route('admin.inventory-transactions.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Stock In</div>
                <div class="stat-val">{{ number_format($totalIn, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-arrow-down-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Stock Out</div>
                <div class="stat-val">{{ number_format($totalOut, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-arrow-up-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Net Movement</div>
                <div class="stat-val">{{ number_format($netMovement, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-exchange-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Products Tracked</div>
                <div class="stat-val">{{ number_format($totalProductsTracked) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-box-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Warehouses Tracked</div>
                <div class="stat-val">{{ number_format($totalWarehousesTracked) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-building-2-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Zero/Negative Balances</div>
                <div class="stat-val">{{ number_format($zeroOrNegativeCount) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-alarm-warning-line"></i>
            </div>
        </div>
    </div>

    <div class="nx-card mb-3">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Current Stock Balance</div>
                <div class="nx-card-sub">Computed live from every movement below, per product/warehouse</div>
            </div>
        </div>

        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($currentStock as $row)
                            <tr>
                                <td>{{ $row['product'] }}</td>
                                <td>{{ $row['warehouse'] }}</td>
                                <td class="text-end {{ $row['balance'] <= 0 ? 'text-danger' : '' }}">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">No stock movements recorded yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Transaction History</div>
                <div class="nx-card-sub">Every movement, most recent first</div>
            </div>
        </div>

        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th class="text-end">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $row)
                            <tr>
                                <td>{{ optional($row['date'])->format('d M, Y') }}</td>
                                <td>{{ $row['type'] }}</td>
                                <td>{{ $row['reference'] }}</td>
                                <td>{{ $row['product'] }}</td>
                                <td>{{ $row['warehouse'] }}</td>
                                <td class="text-end {{ $row['quantity'] < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $row['quantity'] > 0 ? '+' : '' }}{{ number_format($row['quantity'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No stock movements recorded yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
