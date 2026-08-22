<?php

namespace App\Services;

use App\Models\ExpenseClaim;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ExpenseClaimService
{
    public function create(array $data, ?UploadedFile $receipt): ExpenseClaim
    {
        if ($receipt) {
            $data['receipt_path'] = $this->storeFile($receipt);
        }

        return ExpenseClaim::create($data);
    }

    public function update(ExpenseClaim $expenseClaim, array $data, ?UploadedFile $receipt): ExpenseClaim
    {
        if ($receipt) {
            $this->deleteFile($expenseClaim->receipt_path);
            $data['receipt_path'] = $this->storeFile($receipt);
        }

        $expenseClaim->update($data);
        return $expenseClaim;
    }

    public function delete(ExpenseClaim $expenseClaim): bool
    {
        $this->deleteFile($expenseClaim->receipt_path);
        return $expenseClaim->delete();
    }

    public function approve(ExpenseClaim $expenseClaim): ExpenseClaim
    {
        $expenseClaim->update(['approval_status' => 'approved']);
        return $expenseClaim;
    }

    public function reject(ExpenseClaim $expenseClaim): ExpenseClaim
    {
        $expenseClaim->update(['approval_status' => 'rejected']);
        return $expenseClaim;
    }

    public function markReimbursed(ExpenseClaim $expenseClaim, ?string $paymentDate, ?string $method): ExpenseClaim
    {
        if ($expenseClaim->approval_status !== 'approved') {
            throw new \RuntimeException('Only an approved claim can be marked as reimbursed.');
        }

        if ($expenseClaim->payment_status === 'paid') {
            throw new \RuntimeException('This claim has already been reimbursed.');
        }

        $expenseClaim->update([
            'payment_status' => 'paid',
            'payment_date' => $paymentDate ?: now()->toDateString(),
            'reimbursement_method' => $method,
        ]);

        return $expenseClaim;
    }

    protected function storeFile(UploadedFile $file): string
    {
        return $file->store('receipts/expense-claims', 'public');
    }

    protected function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
