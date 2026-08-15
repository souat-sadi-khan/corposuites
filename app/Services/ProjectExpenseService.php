<?php

namespace App\Services;

use App\Models\ProjectExpense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProjectExpenseService
{
    public function create(array $data, ?UploadedFile $receipt = null): ProjectExpense
    {
        if ($receipt) {
            $data['receipt_path'] = $this->storeFile($receipt);
        }

        // Explicit, not left to the column's DB-level default — Eloquent's
        // create() does not re-fetch afterward, so an in-memory model left
        // to the default would report a blank approval_status until the
        // next query, which would have shown up wrong in the very next
        // activity-log entry and JSON response.
        $data['approval_status'] = $data['approval_status'] ?? 'pending';

        return ProjectExpense::create($data);
    }

    public function update(ProjectExpense $expense, array $data, ?UploadedFile $receipt = null): ProjectExpense
    {
        $this->guardEditable($expense);

        if ($receipt) {
            $this->deleteFile($expense->receipt_path);
            $data['receipt_path'] = $this->storeFile($receipt);
        }

        $expense->update($data);

        return $expense->fresh();
    }

    public function delete(ProjectExpense $expense): bool
    {
        $this->deleteFile($expense->receipt_path);

        return $expense->delete();
    }

    public function approve(ProjectExpense $expense, int $adminId): ProjectExpense
    {
        if ($expense->approval_status !== 'pending') {
            throw new RuntimeException('Only a pending expense can be approved.');
        }

        $expense->update([
            'approval_status' => 'approved',
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);

        return $expense->fresh();
    }

    public function reject(ProjectExpense $expense): ProjectExpense
    {
        if ($expense->approval_status !== 'pending') {
            throw new RuntimeException('Only a pending expense can be rejected.');
        }

        $expense->update([
            'approval_status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return $expense->fresh();
    }

    /**
     * Once approved, an expense is a figure that may already feed a
     * profitability figure; once billed on a live Project Invoice, it is a
     * figure the client has actually been charged — editing either
     * afterward would let those numbers silently disagree with what was
     * reviewed or billed. Guards edit only, not delete — an admin can still
     * remove the record outright as a deliberate corrective action, the
     * same edit-locked-but-deletable shape SalesCommission uses after a
     * commission is marked paid.
     */
    protected function guardEditable(ProjectExpense $expense): void
    {
        if ($expense->approval_status === 'approved') {
            throw new RuntimeException('This expense has already been approved and can no longer be edited.');
        }

        if ($expense->is_invoiced) {
            throw new RuntimeException('This expense has already been billed to the client and can no longer be edited.');
        }
    }

    protected function storeFile(UploadedFile $file): string
    {
        return $file->store('receipts/project-expenses', 'public');
    }

    protected function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
