@extends('admin.layout.app', ['title' => 'Escalation History'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="ticketEscalationSearch" placeholder="Search by ticket subject or number">
        </div>

        <select id="ticketFilter" class="form-select form-select-sm w-auto">
            <option value="">All Tickets</option>
            @foreach ($tickets as $ticket)
                <option value="{{ $ticket->id }}">{{ $ticket->ticket_number }} — {{ $ticket->subject }}</option>
            @endforeach
        </select>

        <select id="ruleFilter" class="form-select form-select-sm w-auto">
            <option value="">All Rules</option>
            @foreach ($rules as $rule)
                <option value="{{ $rule->id }}">{{ $rule->name }}</option>
            @endforeach
        </select>

        <div class="tl-spacer"></div>
    </div>

    <!-- Table Card -->
    <div class="nx-card tl-card">
        <div class="table-responsive">
            <table id="ticketEscalationTable" data-url="{{ route('admin.ticket-escalations.index') }}" class="tl-table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ticket</th>
                        <th>Rule Applied</th>
                        <th>Priority Change</th>
                        <th>Escalated To</th>
                        <th>Escalated At</th>
                        <th>Status</th>
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
    <script src="{{ asset('assets/system/js/pages/ticket-escalations.js') }}"></script>
@endpush
