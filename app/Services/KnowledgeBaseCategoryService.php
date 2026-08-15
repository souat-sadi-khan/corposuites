<?php

namespace App\Services;

use App\Models\KnowledgeBaseCategory;

class KnowledgeBaseCategoryService
{
    public function create(array $data): KnowledgeBaseCategory
    {
        return KnowledgeBaseCategory::create($data);
    }

    public function update(KnowledgeBaseCategory $knowledgeBaseCategory, array $data): KnowledgeBaseCategory
    {
        $knowledgeBaseCategory->update($data);

        return $knowledgeBaseCategory->fresh();
    }

    public function delete(KnowledgeBaseCategory $knowledgeBaseCategory): bool
    {
        return $knowledgeBaseCategory->delete();
    }
}
