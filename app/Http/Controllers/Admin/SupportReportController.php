<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EscalationRule;
use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketCategory;
use App\Models\TicketEscalation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SupportReportController extends Controller
{
    /**
     * Support Reports — the module's landing dashboard: the headline
     * figures from every Support screen on one page, plus a directory
     * linking through to each.
     *
     * Pure read-only report: no new table/Model/Service/Request, the same
     * closing-module role Asset Reports/Financial Reports/Project
     * Profitability Reports play for their own sections. Per this project's
     * established precedent, every figure here is computed locally rather
     * than shared with the individual screens it summarises.
     *
     * Tickets are filtered by category and creation-date range; every
     * ticket-scoped panel below (assignments, escalations) is then further
     * scoped to those same filtered ticket ids, the same "scope every
     * panel to the filtered set" approach Asset Reports uses for its own
     * assignment/maintenance/disposal panels. Knowledge Base counts are
     * deliberately NOT scoped by the ticket filter — a KB article lives in
     * its own category taxonomy, cross-referencing a ticket topic only
     * optionally, so it is reported as a global content-library snapshot
     * rather than an activity metric tied to a date range.
     */
    public function index(Request $request)
    {
        $categoryId = $request->ticket_category_id;
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : null;

        $categories = TicketCategory::active()->orderBy('name')->get();

        $tickets = Ticket::with(['category'])
            ->when($categoryId, fn ($q) => $q->where('ticket_category_id', $categoryId))
            ->when($dateFrom, fn ($q) => $q->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('created_at', '<=', $dateTo))
            ->get();

        $resolvedTickets = $tickets->filter(fn ($t) => $t->resolved_at !== null);

        $totals = [
            'total_tickets' => $tickets->count(),
            'open_tickets' => $tickets->whereNotIn('ticket_status', Ticket::CLOSED_STATUSES)->count(),
            'overdue_tickets' => $tickets->filter(fn ($t) => $t->is_overdue)->count(),
            'response_breaches' => $tickets->filter(fn ($t) => $t->is_response_breached)->count(),
            'resolution_breaches' => $tickets->filter(fn ($t) => $t->is_resolution_breached)->count(),
            'without_sla' => $tickets->whereNull('sla_policy_id')->count(),
            'avg_resolution_hours' => $resolvedTickets->isNotEmpty()
                ? round($resolvedTickets->avg(fn ($t) => $t->created_at->diffInHours($t->resolved_at)), 1)
                : null,
        ];

        $ticketIds = $tickets->pluck('id');

        return view('admin.support-reports.index', [
            'categories' => $categories,
            'categoryId' => $categoryId,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
            'totals' => $totals,
            'byCategory' => $this->byCategory($tickets),
            'byStatus' => $this->byStatus($tickets),
            'assignments' => $this->assignmentSummary($ticketIds),
            'escalations' => $this->escalationSummary($ticketIds),
            'recentEscalations' => $this->recentEscalations($ticketIds),
            'knowledgeBase' => $this->knowledgeBaseSummary(),
        ]);
    }

    protected function byCategory(Collection $tickets): Collection
    {
        return $tickets
            ->groupBy(fn ($ticket) => $ticket->category->name ?? 'Uncategorised')
            ->map->count()
            ->sortDesc();
    }

    protected function byStatus(Collection $tickets): Collection
    {
        return $tickets
            ->groupBy(fn ($ticket) => $ticket->ticket_status_label)
            ->map->count()
            ->sortDesc();
    }

    /**
     * Current handling position across the tickets in scope.
     */
    protected function assignmentSummary(Collection $ticketIds): array
    {
        $active = TicketAssignment::whereIn('ticket_id', $ticketIds)
            ->where('assignment_status', 'assigned')
            ->get();

        return [
            'active' => $active->count(),
            'agents' => $active->pluck('assigned_to')->unique()->count(),
            'unassigned' => $ticketIds->count() - $active->pluck('ticket_id')->unique()->count(),
        ];
    }

    /**
     * Escalation activity across the tickets in scope.
     */
    protected function escalationSummary(Collection $ticketIds): array
    {
        $escalations = TicketEscalation::whereIn('ticket_id', $ticketIds)->active()->get();

        return [
            'total' => $escalations->count(),
            'priority_changed' => $escalations->filter(
                fn ($e) => $e->previous_priority && $e->new_priority && $e->previous_priority !== $e->new_priority
            )->count(),
            'reassigned' => $escalations->whereNotNull('escalated_to_admin_id')->count(),
            'active_rules' => EscalationRule::active()->count(),
        ];
    }

    /**
     * The most recent escalations for the tickets in scope — the
     * actionable list, capped so the dashboard stays a dashboard.
     */
    protected function recentEscalations(Collection $ticketIds): Collection
    {
        return TicketEscalation::with(['ticket', 'escalationRule', 'escalatedToAdmin'])
            ->whereIn('ticket_id', $ticketIds)
            ->active()
            ->orderByDesc('escalated_at')
            ->limit(10)
            ->get();
    }

    /**
     * A global content-library snapshot, deliberately unfiltered by the
     * ticket date/category filter above — see the class-level note.
     */
    protected function knowledgeBaseSummary(): array
    {
        return [
            'total' => KnowledgeBaseArticle::count(),
            'published' => KnowledgeBaseArticle::where('article_status', 'published')->count(),
            'draft' => KnowledgeBaseArticle::where('article_status', 'draft')->count(),
            'archived' => KnowledgeBaseArticle::where('article_status', 'archived')->count(),
            'public' => KnowledgeBaseArticle::where('visibility', 'public')->count(),
        ];
    }
}
