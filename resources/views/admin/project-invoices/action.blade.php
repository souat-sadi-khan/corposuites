<div class="tl-actions">
    @if ($row->invoice_status === 'draft')
        <button class="tl-icon-btn text-primary invoice-mark-sent-btn" data-url="{{ route('admin.project-invoices.mark-sent', $row->id) }}" title="Mark Sent">
            <i class="ri-send-plane-line"></i>
        </button>
    @endif

    @if (in_array($row->invoice_status, ['sent', 'partially_paid']))
        <button class="tl-icon-btn text-success invoice-record-payment-btn" data-url="{{ route('admin.project-invoices.record-payment', $row->id) }}" title="Record Payment">
            <i class="ri-money-dollar-circle-line"></i>
        </button>
    @endif

    @if (! in_array($row->invoice_status, ['paid', 'cancelled']))
        <button class="tl-icon-btn text-danger invoice-cancel-btn" data-url="{{ route('admin.project-invoices.cancel', $row->id) }}" title="Cancel">
            <i class="ri-close-circle-line"></i>
        </button>
    @endif

    @if (! in_array($row->invoice_status, ['paid', 'cancelled']))
        <!-- Edit -->
        <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.project-invoices.edit', $row->id) }}" title="Edit">
            <i class="ri-pencil-line"></i>
        </button>
    @else
        <span class="tl-icon-btn disabled" title="{{ ucfirst($row->invoice_status) }} — locked from editing">
            <i class="ri-lock-line"></i>
        </span>
    @endif

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.project-invoices.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
