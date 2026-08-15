@extends('admin.layout.app', ['title' => 'Product Reports'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Product Overview</h2>
            <div class="sec-sub">Aggregated snapshot across products, variants, bundles, and pricing</div>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Products</div>
                <div class="stat-val">{{ number_format($totalProducts) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-box-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Active Products</div>
                <div class="stat-val">{{ number_format($activeProducts) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Products Missing a Price</div>
                <div class="stat-val">{{ number_format($productsWithoutPrice) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-price-tag-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Average Selling Price</div>
                <div class="stat-val">{{ number_format($avgSellingPrice ?? 0, 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-money-dollar-circle-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Product Variants</div>
                <div class="stat-val">{{ number_format($totalVariants) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-stack-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Active Variants</div>
                <div class="stat-val">{{ number_format($activeVariants) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-stack-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Product Bundles</div>
                <div class="stat-val">{{ number_format($totalBundles) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-archive-2-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Active Discount Rules</div>
                <div class="stat-val">{{ number_format($activeDiscountRules) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-percent-line"></i>
            </div>
        </div>
    </div>

    <div class="twin-row mb-3">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Products by Category</div>
                    <div class="nx-card-sub">Active products grouped by category</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Products by Brand</div>
                    <div class="nx-card-sub">Active products grouped by brand</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="brandChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Products Missing a Price</div>
                <div class="nx-card-sub">Next 10 active products with no selling price set</div>
            </div>
        </div>

        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productsMissingPrice as $product)
                            <tr>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">All active products have a price set</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script>
        const categoryLabels = @json($productsByCategory->keys());
        const categoryData = @json($productsByCategory->values());

        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Products',
                    data: categoryData,
                    backgroundColor: 'rgba(101,103,245,.7)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        const brandLabels = @json($productsByBrand->keys());
        const brandData = @json($productsByBrand->values());

        new Chart(document.getElementById('brandChart'), {
            type: 'doughnut',
            data: {
                labels: brandLabels,
                datasets: [{
                    data: brandData,
                    backgroundColor: ['#16a34a', '#dc2626', '#f59e0b', '#0ea5e9', '#a855f7', '#6366f5']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
@endpush
