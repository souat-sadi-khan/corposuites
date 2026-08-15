<?php

namespace App\Services;

use App\Models\SlaPolicy;
use App\Models\Ticket;
use Illuminate\Support\Carbon;

class TicketService
{
    public function create(array $data): Ticket
    {
        $data['ticket_number'] = $this->generateTicketNumber();

        return Ticket::create($this->withDerivedFields($data));
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        // Issued once; any future Ticket Assignment/SLA/Escalation record
        // filed against this ticket will reference it, so it must never be
        // re-issued.
        unset($data['ticket_number']);

        $ticket->update($this->withDerivedFields($data, $ticket));

        return $ticket->fresh();
    }

    public function delete(Ticket $ticket): bool
    {
        return $ticket->delete();
    }

    /**
     * Stamps the moment a ticket was first actually responded to — a plain
     * fact, set once, never recomputed by withDerivedFields(). Guarded
     * against double-stamping the same way every other "quick action" in
     * this project guards its own state transition (e.g.
     * ProjectInvoiceService::markSent()).
     */
    public function recordFirstResponse(Ticket $ticket): Ticket
    {
        if ($ticket->first_responded_at) {
            throw new \RuntimeException('This ticket has already had its first response recorded.');
        }

        $ticket->update(['first_responded_at' => now()]);

        return $ticket->fresh();
    }

    /**
     * A small, single-field state change — used by Escalation Rules when a
     * breach auto-bumps a ticket's priority, the same "dedicated method for
     * one specific transition" pattern OpportunityService::updateStage()
     * already established, rather than requiring the caller to assemble a
     * full TicketRequest-shaped payload for a one-field change. Routes
     * through withSlaFields() so the due-date targets are recomputed
     * against the new priority immediately, not left stale until the next
     * unrelated edit.
     */
    public function changePriority(Ticket $ticket, string $newPriority): Ticket
    {
        $data = $this->withSlaFields(['priority' => $newPriority], $ticket->ticket_status, $ticket);

        $ticket->update($data);

        return $ticket->fresh();
    }

    /**
     * A resolved/closed ticket is stamped with the timestamp it actually
     * reached that state (now, if none was already recorded); moving it back
     * off either clears the matching timestamp, so it can never claim it was
     * resolved or closed at a moment it was not — the same "service owns
     * completion consistency" pattern Project/Milestone/Task/Asset Disposal
     * already use. Also resolves the ticket's SLA targets fresh from its
     * current priority on every save (see withSlaFields() below).
     */
    protected function withDerivedFields(array $data, ?Ticket $ticket = null): array
    {
        $status = $data['ticket_status'] ?? $ticket?->ticket_status;

        // Cleared based on whether the timestamp is actually set, not on the
        // ticket's *previous* status value — a ticket that went
        // resolved → closed still carries a resolved_at while closed, so
        // checking "was the previous status resolved" would miss clearing
        // it on a closed → open move (caught by this module's own tinker
        // test: reopening a resolved-then-closed ticket left resolved_at
        // stuck because the ticket's prior status was "closed", not
        // "resolved", even though resolved_at was still populated).
        if ($status === 'resolved') {
            $data['resolved_at'] = $data['resolved_at'] ?? $ticket?->resolved_at ?? now();
        } elseif ($ticket && $ticket->resolved_at) {
            $data['resolved_at'] = null;
        }

        if ($status === 'closed') {
            $data['closed_at'] = $data['closed_at'] ?? $ticket?->closed_at ?? now();
            // A ticket cannot be closed without having been resolved first —
            // if it was closed directly from an earlier state, backfill the
            // resolved timestamp to the same moment rather than leaving it
            // null on a ticket that is, by definition, no longer open.
            $data['resolved_at'] = $data['resolved_at'] ?? $ticket?->resolved_at ?? $data['closed_at'];
        } elseif ($ticket && $ticket->closed_at) {
            $data['closed_at'] = null;
        }

        // A ticket can't have reached a closed bucket without someone having
        // responded to it first — if it got there with first_responded_at
        // still unset, backfill it to the same moment rather than leaving a
        // resolved/closed ticket permanently flagged as never responded to.
        if (in_array($status, Ticket::CLOSED_STATUSES, true) && ! ($data['first_responded_at'] ?? $ticket?->first_responded_at)) {
            $data['first_responded_at'] = $data['resolved_at'] ?? now();
        }

        return $this->withSlaFields($data, $status, $ticket);
    }

    /**
     * Resolves the applicable SlaPolicy for the ticket's own fixed priority
     * enum (never the optional, admin-configurable TicketPriority — the
     * same reasoning ticket_status_id/ticket_priority_id both stay
     * independent of the always-present base logic) and (re)computes the
     * two due-date targets from it, anchored on the ticket's creation time.
     *
     * Deliberately recomputed fresh on every save, not locked at creation —
     * unlike a stored financial snapshot (e.g. AssetDisposal's
     * book_value_at_disposal), there is no policy-versioning/history
     * concept anywhere in this project; SLA policies are a plain, in-place-
     * edited master the same as every other lookup, so "the current target"
     * is the only meaningful answer this module can give. If the priority
     * governing a ticket changes, or the matching policy's hours are edited
     * before the ticket is next saved, the deadlines update to reflect that
     * — arguably the more useful behaviour for an operational SLA anyway.
     */
    protected function withSlaFields(array $data, ?string $status, ?Ticket $ticket): array
    {
        $priority = $data['priority'] ?? $ticket?->priority;
        $anchor = $ticket?->created_at ?? Carbon::now();

        $policy = $priority
            ? SlaPolicy::where('priority', $priority)->where('status', true)->first()
            : null;

        if ($policy) {
            $data['sla_policy_id'] = $policy->id;
            $data['first_response_due_at'] = $anchor->copy()->addMinutes((int) round((float) $policy->response_time_hours * 60));
            $data['resolution_due_at'] = $anchor->copy()->addMinutes((int) round((float) $policy->resolution_time_hours * 60));
        } else {
            $data['sla_policy_id'] = null;
            $data['first_response_due_at'] = null;
            $data['resolution_due_at'] = null;
        }

        return $data;
    }

    protected function generateTicketNumber(): string
    {
        $lastId = Ticket::max('id') ?? 0;

        return 'TKT-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
