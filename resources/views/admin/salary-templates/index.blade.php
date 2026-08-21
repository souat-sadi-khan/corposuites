@extends('admin.layout.app', ['title' => 'Salary Templates', 'modal' => 'lg'])

@section('content')
    <style>
        /* =====================================================
           Select2 "rich" option template (component picker)
        ===================================================== */

        .sel-opt-rich-info {
            flex: 1;
            min-width: 0;
        }

        .sel-opt-rich-name-row {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .sel-opt-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.4;
        }

        .sel-opt-type-badge.bg-success-subtle {
            background: rgba(25, 135, 84, 0.10);
        }

        .sel-opt-type-badge.bg-danger-subtle {
            background: rgba(220, 53, 69, 0.10);
        }

        .sel-opt-percentage {
            font-size: 11px;
            font-weight: 700;
            color: var(--tx-2);
            background: var(--bg-hover);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .sel-opt-rich-desc {
            margin-top: 2px;
            font-size: 11px;
            color: var(--tx-3);
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* =====================================================
           Salary Component Rows (one row per component)
        ===================================================== */

        .salary-component-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 170px 42px;
            gap: 10px;
            align-items: center;
            padding: 10px 12px;
            border: 1px solid var(--border-lt);
            border-radius: 10px;
            background: var(--bg-base);
            margin-bottom: 8px;
        }

        .salary-component-row .fm-field {
            margin: 0;
        }

        .salary-component-action {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .salary-component-action .btn,
        .remove-salary-template-component {
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .salary-template-component-rows:empty::before {
            content: 'No components added yet — click "Add Component" below to build the earnings / deductions breakdown.';
            display: block;
            font-size: 12.5px;
            color: var(--tx-3);
            padding: 18px;
            border: 1px dashed var(--border);
            border-radius: 10px;
            text-align: center;
        }

        .salary-template-components-hdr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .salary-template-components-hdr h6 {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--tx-3);
            margin: 0;
            display: flex;
            align-items: center;
        }

        /* =====================================================
           Salary Summary (calculation area)
        ===================================================== */

        .salary-summary {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            background: var(--bg-surface);
        }

        .salary-summary-hdr {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-lt);
            font-size: 11px;
            font-weight: 700;
            color: var(--tx-3);
            text-transform: uppercase;
            letter-spacing: .5px;
            background: var(--bg-base);
        }

        .salary-summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-lt);
            font-size: 13px;
        }

        .salary-summary-row:last-child {
            border-bottom: 0;
        }

        .salary-summary-row span {
            color: var(--tx-2);
        }

        .salary-summary-row strong {
            font-size: 13px;
            font-weight: 700;
            color: var(--tx-1);
        }

        .salary-summary-row small {
            font-size: 11px;
            font-weight: 600;
        }

        .salary-gross-row {
            background: var(--accent-s);
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .salary-gross-row span {
            color: var(--tx-1);
            font-weight: 700;
        }

        .salary-gross-row strong {
            font-size: 16px;
            color: var(--accent);
        }

        /* =====================================================
           Footer "Add Component" — distinct soft-accent button
        ===================================================== */

        .btn-nx-soft-accent {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--accent-m);
            background: var(--accent-s);
            color: var(--accent);
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background .15s, box-shadow .15s;
            white-space: nowrap;
        }

        .btn-nx-soft-accent:hover {
            background: var(--accent-m);
        }

        .btn-nx-soft-accent:disabled {
            opacity: .5;
            cursor: not-allowed;
        }
    </style>

    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="salaryTemplateSearch" placeholder="Search Salary Templates">
        </div>

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

        <select id="salaryTemplatePayTypeFilter" class="form-select form-select-sm w-auto">
            <option value="">All Pay Types</option>
            <option value="monthly">Monthly</option>
            <option value="daily">Daily</option>
            <option value="commission">Commission-based</option>
        </select>

        <div class="tl-spacer"></div>

        <!-- How To -->
        <button id="openModal" data-url="{{ route('admin.salary-templates.how.to') }}" class="btn-nx-outline">
            <i class="ri-question-mark"></i>
        </button>

        <!-- Add Button -->
        <button id="openModal" data-url="{{ route('admin.salary-templates.create') }}" class="btn-nx-primary">
            <i class="ri-add-line"></i>
            Add Salary Template
        </button>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="salaryTemplateTable" data-url="{{ route('admin.salary-templates.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Pay Type</th>
                        <th>Salary</th>
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
    <script src="{{ asset('assets/system/js/pages/salary-templates.js') }}"></script>
@endpush
