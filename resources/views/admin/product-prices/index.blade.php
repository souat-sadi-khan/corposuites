@extends('admin.layout.app', ['title' => 'Product Prices', 'modal' => 'lg'])

@section('content')
    <div class="tl-toolbar">
        <select id="productFilter" class="form-select form-select-sm w-auto">
            <option value="">All Products</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }}</option>
            @endforeach
        </select>

        <select id="tierFilter" class="form-select form-select-sm w-auto">
            <option value="">All Tiers</option>
            @foreach($priceTiers as $tier)
                <option value="{{ $tier->id }}">{{ $tier->name }}</option>
            @endforeach
        </select>

        <div class="tl-spacer"></div>

        <a href="{{ route('admin.price-tiers.index') }}" class="btn-nx-outline me-2">
            <i class="ri-price-tag-3-line"></i>
            Manage Price Tiers
        </a>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.product-prices.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Product Price
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="productPriceTable" data-url="{{ route('admin.product-prices.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Tier</th>
                        <th>Price</th>
                        <th class="no-sort text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="tl-footer">
            <div class="tl-info" id="tlInfo"></div>
            <div class="tl-pagination">
                <button class="tl-page-btn" id="tlPrev" title="Previous page">
                    <i class="ri-arrow-left-s-line"></i>
                </button>
                <button class="tl-page-btn" id="tlNext" title="Next page">
                    <i class="ri-arrow-right-s-line"></i>
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
    <script src="{{ asset('assets/system/js/pages/product-prices.js') }}"></script>
@endpush
