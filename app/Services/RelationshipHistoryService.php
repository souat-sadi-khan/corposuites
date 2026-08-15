<?php

namespace App\Services;

use App\Models\RelationshipHistory;

class RelationshipHistoryService
{
    public function create(array $data): RelationshipHistory
    {
        return RelationshipHistory::create($data);
    }

    public function update(RelationshipHistory $relationshipHistory, array $data): RelationshipHistory
    {
        $relationshipHistory->update($data);
        return $relationshipHistory;
    }

    public function delete(RelationshipHistory $relationshipHistory): bool
    {
        return $relationshipHistory->delete();
    }
}
