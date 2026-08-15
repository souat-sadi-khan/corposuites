@extends('admin.layout.app', ['title' => 'POS Terminal'])

@section('content')
    <div class="tl-toolbar">
        <a href="{{ route('admin.pos.index') }}" class="btn-nx-outline">
            <i class="ri-arrow-left-line"></i>
            Sales History
        </a>
        <div class="tl-spacer"></div>
        <div class="tl-search" style="max-width:320px;">
            <i class="ri-search-line"></i>
            <input type="text" id="posProductSearch" placeholder="Search products by name or SKU">
        </div>
    </div>

    <div class="pos-terminal-layout">
        <div class="nx-card pos-product-panel">
            <div class="pos-product-grid" id="posProductGrid">
                @foreach($products as $product)
                    <div class="pos-product-card" data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-sku="{{ $product->sku }}" data-price="{{ $product->selling_price }}">
                        <div class="pos-product-name">{{ $product->name }}</div>
                        <div class="pos-product-sku">{{ $product->sku }}</div>
                        <div class="pos-product-price">{{ number_format($product->selling_price, 2) }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="nx-card pos-cart-panel">
            <h5 class="mb-3">Current Sale</h5>

            <div class="fm-field mb-2">
                <label>Customer</label>
                <select id="posCustomerSelect" class="form-select select">
                    <option value="">Walk-in Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="table-responsive pos-cart-table-wrap">
                <table class="table pos-cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="width:70px;">Qty</th>
                            <th style="width:90px;">Price</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="posCartBody">
                        <tr class="pos-cart-empty-row">
                            <td colspan="4" class="text-center text-muted py-3">Cart is empty — click a product to add it</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pos-totals mt-3">
                <div class="d-flex justify-content-between">
                    <span>Subtotal</span>
                    <b id="posSubtotal">0.00</b>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Discount</span>
                    <b id="posDiscount">0.00</b>
                </div>
                <div class="d-flex justify-content-between fs-5">
                    <span>Grand Total</span>
                    <b id="posGrandTotal">0.00</b>
                </div>
            </div>

            <div class="fm-grid mt-3">
                <div class="fm-field">
                    <label>Payment Method</label>
                    <select id="posPaymentMethod" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="fm-field">
                    <label>Amount Tendered</label>
                    <input type="number" step="0.01" min="0" id="posAmountTendered" class="form-control">
                </div>
                <div class="fm-field fm-full">
                    <div class="d-flex justify-content-between">
                        <span>Change Due</span>
                        <b id="posChangeDue">0.00</b>
                    </div>
                </div>
            </div>

            <button type="button" id="posCheckoutBtn" class="btn-nx-primary w-100 mt-3">
                <i class="ri-shopping-cart-2-line me-1"></i> Complete Sale
            </button>
            <button type="button" id="posCheckoutBtnLoading" class="btn-nx-primary w-100 mt-3" disabled style="display:none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Processing...
            </button>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .pos-terminal-layout { display: grid; grid-template-columns: 1fr 380px; gap: 16px; align-items: start; }
        .pos-product-panel { padding: 16px; }
        .pos-product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; max-height: 70vh; overflow-y: auto; }
        .pos-product-card { border: 1px solid var(--bs-border-color, #e5e5e5); border-radius: 8px; padding: 10px; cursor: pointer; transition: box-shadow .15s; }
        .pos-product-card:hover { box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        .pos-product-name { font-weight: 600; font-size: .9rem; }
        .pos-product-sku { font-size: .75rem; color: #888; }
        .pos-product-price { margin-top: 6px; font-weight: 600; }
        .pos-cart-panel { padding: 16px; position: sticky; top: 16px; }
        .pos-cart-table-wrap { max-height: 260px; overflow-y: auto; }
        .pos-cart-qty { width: 55px; }
        @media (max-width: 992px) {
            .pos-terminal-layout { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.posCheckoutUrl = "{{ route('admin.pos.checkout') }}";
    </script>
    <script src="{{ asset('assets/system/js/pages/pos-terminal.js') }}"></script>
@endpush
