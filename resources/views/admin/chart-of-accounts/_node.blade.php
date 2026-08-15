@php
    $typeBadgeColors = [
        'asset' => 'success',
        'liability' => 'danger',
        'equity' => 'purple',
        'revenue' => 'info',
        'expense' => 'warning',
    ];
@endphp

@foreach($accounts->where('parent_id', $parentId) as $account)
    @php $children = $accounts->where('parent_id', $account->id); @endphp
    <li class="coa-node" data-name="{{ strtolower($account->code . ' ' . $account->name) }}">
        <div class="coa-node-row">
            <span class="coa-toggle {{ $children->isEmpty() ? 'leaf' : '' }}">
                <i class="ri-arrow-right-s-line"></i>
            </span>
            <span class="coa-code">{{ $account->code }}</span>
            <span class="coa-name">{{ $account->name }}</span>
            <span class="badge bg-{{ $typeBadgeColors[$account->account_type] ?? 'secondary' }}">{{ ucfirst($account->account_type) }}</span>
            @if($account->is_group)
                <span class="badge bg-secondary">Group</span>
            @endif
            @if($account->accountType)
                <span class="badge bg-light text-dark border">{{ $account->accountType->name }}</span>
            @endif
            <div class="fm-field mb-0">
                <div class="form-check form-switch mb-0">
                    <input data-url="{{ route('admin.chart-of-accounts.status', $account->id) }}" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status{{ $account->id }}" {{ $account->status ? 'checked' : '' }} data-id="{{ $account->id }}">
                </div>
            </div>
            <div class="coa-actions">
                <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.chart-of-accounts.create', ['parent_id' => $account->id]) }}" title="Add Sub-account">
                    <i class="ri-add-line"></i>
                </button>
                <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.chart-of-accounts.edit', $account->id) }}" title="Edit">
                    <i class="ri-pencil-line"></i>
                </button>
                <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $account->id }}" data-url="{{ route('admin.chart-of-accounts.destroy', $account->id) }}" data-del="1" title="Delete">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>

        @if($children->isNotEmpty())
            <ul class="coa-children">
                @include('admin.chart-of-accounts._node', ['accounts' => $accounts, 'parentId' => $account->id])
            </ul>
        @endif
    </li>
@endforeach
