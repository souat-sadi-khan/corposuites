@extends('admin.layout.app', ['title' => 'Product Images', 'modal' => 'lg'])

@section('content')
    @if(request('product_id'))
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <span><i class="ri-filter-3-line me-1"></i> Showing images for the selected product.</span>
            <a href="{{ route('admin.product-images.index') }}" class="btn-nx-outline btn-sm">Clear Filter</a>
        </div>
    @endif

    <div class="tl-toolbar">
        <div class="tl-filter-wrap">
            <button class="tl-filter-btn" id="tlFilterBtn" title="Filter">
                <i class="ri-equalizer-line"></i>
            </button>

            <div class="tl-filter-dd" id="tlFilterDd">
                <div class="tl-filter-dd-title">
                    Filter by Status
                </div>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="1" checked>
                    Active
                </label>
                <label class="tl-filter-chk">
                    <input type="checkbox" value="0" checked>
                    Inactive
                </label>
            </div>
        </div>

        @unless(request('product_id'))
            <select id="productFilter" class="form-select form-select-sm w-auto">
                <option value="">All Products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
        @endunless

        <div class="tl-spacer"></div>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.product-images.create', request('product_id') ? ['product_id' => request('product_id')] : []) }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Image
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="productImageTable" data-url="{{ route('admin.product-images.index') }}" data-product-id="{{ request('product_id') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Preview</th>
                        <th>Product</th>
                        <th>Status</th>
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
    <script src="{{ asset('assets/system/js/pages/product-images.js') }}"></script>
@endpush
