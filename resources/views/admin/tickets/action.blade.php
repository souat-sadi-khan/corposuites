<div class="tl-actions">
    @if($row->first_response_due_at && ! $row->first_responded_at)
        <!-- Record First Response -->
        <button class="tl-icon-btn ticket-record-response-btn" data-url="{{ route('admin.tickets.record-first-response', $row->id) }}" title="Record First Response">
            <i class="ri-chat-check-line"></i>
        </button>
    @endif

    @if(($row->is_response_breached || $row->is_resolution_breached) && ! in_array($row->ticket_status, \App\Models\Ticket::CLOSED_STATUSES, true))
        <!-- Escalate -->
        <button class="tl-icon-btn danger ticket-escalate-btn" data-url="{{ route('admin.tickets.escalate', $row->id) }}" title="Escalate">
            <i class="ri-arrow-up-double-line"></i>
        </button>
    @endif

    <!-- Edit -->
    <button class="tl-icon-btn" id="openModal" data-url="{{ route('admin.tickets.edit', $row->id) }}" title="Edit">
        <i class="ri-pencil-line"></i>
    </button>

    <!-- Delete -->
    <button class="tl-icon-btn danger" id="delete_item" data-id="{{ $row->id }}" data-url="{{ route('admin.tickets.destroy', $row->id) }}" data-del="1" title="Delete">
        <i class="ri-delete-bin-line"></i>
    </button>
</div>
