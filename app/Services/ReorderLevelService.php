<?php

namespace App\Services;

use App\Models\ReorderLevel;

class ReorderLevelService
{
    public function create(array $data): ReorderLevel
    {
        return ReorderLevel::create($data);
    }

    public function update(ReorderLevel $reorderLevel, array $data): ReorderLevel
    {
        $reorderLevel->update($data);

        return $reorderLevel;
    }

    public function delete(ReorderLevel $reorderLevel): bool
    {
        return $reorderLevel->delete();
    }
}
