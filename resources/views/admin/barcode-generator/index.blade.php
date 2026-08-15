@extends('admin.layout.app', ['title' => 'Barcode Generator'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="barcodeProductSearch" placeholder="Search Products">
        </div>
        <div class="tl-spacer"></div>
        <button type="button" class="btn-nx-outline" id="selectAllProducts">Select All</button>
        <button type="button" class="btn-nx-outline" id="clearAllProducts">Clear All</button>
    </div>

    <div class="nx-card tl-card">
        <form method="GET" action="{{ route('admin.barcode-generator.print') }}" target="_blank">
            <div class="table-responsive">
                <table class="tl-table" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th style="width:120px;">Copies</th>
                        </tr>
                    </thead>
                    <tbody id="barcodeProductRows">
                        @forelse($products as $product)
                            <tr class="barcode-product-row" data-name="{{ strtolower($product->name) }}">
                                <td>
                                    <input type="checkbox" class="form-check-input barcode-product-check" name="product_ids[]" value="{{ $product->id }}">
                                </td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->sku }}</td>
                                <td>
                                    <input type="number" min="1" max="100" class="form-control form-control-sm" name="quantity[{{ $product->id }}]" value="1">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No active products available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="tl-footer">
                <button type="submit" class="btn-nx-primary">
                    <i class="ri-printer-line"></i>
                    Generate & Print Barcodes
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('barcodeProductSearch').addEventListener('keyup', function () {
            var term = this.value.trim().toLowerCase();
            document.querySelectorAll('.barcode-product-row').forEach(function (row) {
                row.style.display = row.dataset.name.indexOf(term) !== -1 ? '' : 'none';
            });
        });

        document.getElementById('selectAllProducts').addEventListener('click', function () {
            document.querySelectorAll('.barcode-product-row:not([style*="display: none"]) .barcode-product-check').forEach(function (cb) {
                cb.checked = true;
            });
        });

        document.getElementById('clearAllProducts').addEventListener('click', function () {
            document.querySelectorAll('.barcode-product-check').forEach(function (cb) {
                cb.checked = false;
            });
        });
    </script>
@endpush
