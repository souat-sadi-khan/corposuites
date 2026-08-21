<?php

namespace App\Services;

use App\Models\ApprovalDelegation;
use Illuminate\Support\Carbon;

class ApprovalDelegationService
{
    public function create(array $data): ApprovalDelegation
    {
        return ApprovalDelegation::create($data);
    }

    public function update(ApprovalDelegation $approvalDelegation, array $data): ApprovalDelegation
    {
        $approvalDelegation->update($data);
        return $approvalDelegation;
    }

    public function delete(ApprovalDelegation $approvalDelegation): bool
    {
        return $approvalDelegation->delete();
    }

    /**
     * Resolve the effective approver admin id for a given admin on a given date.
     * If the admin has an active delegation covering that date, the delegate id is
     * returned (following a short chain, guarded against cycles). Otherwise the
     * original id is returned unchanged.
     */
    public function effectiveApproverId(int $adminId, $date = null): int
    {
        $date = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        $current = $adminId;
        $seen = [$adminId];

        // Follow at most a few hops so A->B->C resolves, while a cycle cannot loop forever.
        for ($i = 0; $i < 5; $i++) {
            $delegation = ApprovalDelegation::covering($date)
                ->where('delegator_admin_id', $current)
                ->orderByDesc('id')
                ->first();

            if (!$delegation) {
                break;
            }

            $next = (int) $delegation->delegate_admin_id;

            if (in_array($next, $seen, true)) {
                break;
            }

            $current = $next;
            $seen[] = $next;
        }

        return $current;
    }

    /**
     * Map a list of approver admin ids through their active delegations for the
     * given date, returning a de-duplicated list of effective approver ids.
     *
     * @param  int[]  $adminIds
     * @return int[]
     */
    public function mapApprovers(array $adminIds, $date = null): array
    {
        return collect($adminIds)
            ->map(fn ($id) => $this->effectiveApproverId((int) $id, $date))
            ->unique()
            ->values()
            ->all();
    }
}
