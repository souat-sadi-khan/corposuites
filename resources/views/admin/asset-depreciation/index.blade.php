@extends('admin.layout.app', ['title' => 'Depreciation Calculation'])

@section('content')

    <div class="sec-hdr">
        <div>
            <h2>Depreciation Calculation</h2>
            <div class="sec-sub">Book value and accumulated depreciation, derived from purchase cost and category settings</div>
        </div>
    </div>

    <div class="tl-toolbar mb-3">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center w-100">
            <select name="asset_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Assets</option>
                @foreach($assets as $asset)
                    <option value="{{ $asset->id }}" {{ (string) $assetId === (string) $asset->id ? 'selected' : '' }}>{{ $asset->asset_code }} - {{ $asset->name }}</option>
                @endforeach
            </select>

            <select name="asset_category_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>

            <input type="date" name="as_of_date" class="form-control form-control-sm w-auto" value="{{ $asOfDate }}" onchange="this.form.submit()">

            @if($assetId || $categoryId)
                <a href="{{ route('admin.asset-depreciation.index') }}" class="btn-nx-outline btn-sm">
                    <i class="ri-close-line"></i> Clear Filters
                </a>
            @endif
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Total Cost</div>
                <div class="stat-val">{{ number_format($totals['cost'], 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-money-dollar-box-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Accumulated Depreciation</div>
                <div class="stat-val">{{ number_format($totals['accumulated'], 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-line-chart-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Net Book Value</div>
                <div class="stat-val">{{ number_format($totals['book_value'], 2) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-scales-3-line"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">Depreciating Assets</div>
                <div class="stat-val">
                    {{ $totals['depreciable'] }}
                    @if($totals['not_depreciable'] > 0)
                        <small class="text-muted">/ {{ $totals['assets'] }}</small>
                    @endif
                </div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-hard-drive-3-line"></i>
            </div>
        </div>
    </div>

    @if($selectedAsset && $schedule)
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">{{ $selectedAsset['asset_code'] }} — {{ $selectedAsset['name'] }}</div>
                    <div class="nx-card-sub">
                        {{ $selectedAsset['method_label'] }} over {{ $selectedAsset['life'] }} year(s)
                        &middot; Cost {{ number_format($selectedAsset['cost'], 2) }}
                        &middot; Salvage {{ number_format($selectedAsset['salvage'], 2) }}
                        ({{ rtrim(rtrim(number_format($selectedAsset['salvage_percent'], 2), '0'), '.') }}%)
                        @if($selectedAsset['method'] === 'reducing_balance')
                            &middot; Rate {{ round(2 / max(1, $selectedAsset['life']) * 100, 2) }}% of the written-down value each year
                        @endif
                    </div>
                </div>
            </div>

            <div class="nx-card-body">
                <div style="overflow-x:auto;">
                    <table class="ractivity-tbl">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Period</th>
                                <th class="text-end">Opening Value</th>
                                <th class="text-end">Depreciation</th>
                                <th class="text-end">Accumulated</th>
                                <th class="text-end">Closing Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($schedule as $line)
                                <tr>
                                    <td>{{ $line['year'] }}</td>
                                    <td>{{ $line['period'] }}</td>
                                    <td class="text-end">{{ number_format($line['opening'], 2) }}</td>
                                    <td class="text-end">{{ number_format($line['charge'], 2) }}</td>
                                    <td class="text-end">{{ number_format($line['accumulated'], 2) }}</td>
                                    <td class="text-end">{{ number_format($line['closing'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="nx-card">
        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">Depreciation Position</div>
                <div class="nx-card-sub">
                    As of {{ \Carbon\Carbon::parse($asOfDate)->format('d M, Y') }}
                    @if($totals['not_depreciable'] > 0)
                        &middot; {{ $totals['not_depreciable'] }} asset(s) cannot be depreciated — see the reason in the row
                    @endif
                </div>
            </div>
        </div>

        <div class="nx-card-body">
            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Category</th>
                            <th>Method</th>
                            <th class="text-end">Cost</th>
                            <th class="text-end">Annual</th>
                            <th class="text-end">Accumulated</th>
                            <th class="text-end">Book Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.asset-depreciation.index', array_filter(['asset_id' => $row['asset_id'], 'as_of_date' => $asOfDate])) }}">{{ $row['name'] }}</a>
                                    <div class="text-muted small">{{ $row['asset_code'] }}</div>
                                </td>
                                <td>{{ $row['category'] ?? '-' }}</td>
                                <td>
                                    @if($row['depreciable'])
                                        {{ $row['method_label'] }}
                                        <div class="text-muted small">{{ $row['life'] }} year(s)</div>
                                    @else
                                        <span class="text-danger small">{{ $row['reason'] }}</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $row['cost'] > 0 ? number_format($row['cost'], 2) : '-' }}</td>
                                <td class="text-end">{{ $row['depreciable'] ? number_format($row['annual'], 2) : '-' }}</td>
                                <td class="text-end">{{ $row['depreciable'] ? number_format($row['accumulated'], 2) : '-' }}</td>
                                <td class="text-end">
                                    @if($row['depreciable'])
                                        {{ number_format($row['book_value'], 2) }}
                                        @if($row['fully_depreciated'])
                                            <div class="small text-muted">Fully depreciated</div>
                                        @endif
                                    @elseif($row['cost'] > 0)
                                        {{ number_format($row['book_value'], 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No assets match the selected filters</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($totals['depreciable'] > 0)
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Totals (depreciating assets)</th>
                                <th class="text-end">{{ number_format($totals['cost'], 2) }}</th>
                                <th></th>
                                <th class="text-end">{{ number_format($totals['accumulated'], 2) }}</th>
                                <th class="text-end">{{ number_format($totals['book_value'], 2) }}</th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

@endsection
