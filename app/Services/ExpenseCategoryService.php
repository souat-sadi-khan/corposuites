<?php

namespace App\Services;

use App\Models\ExpenseCategory;

class ExpenseCategoryService
{
    public function create(array $data): ExpenseCategory
    {
        return ExpenseCategory::create($data);
    }

    public function update(ExpenseCategory $expenseCategory, array $data): ExpenseCategory
    {
        $expenseCategory->update($data);

        return $expenseCategory->fresh();
    }

    public function delete(ExpenseCategory $expenseCategory): bool
    {
        return $expenseCategory->delete();
    }
}
