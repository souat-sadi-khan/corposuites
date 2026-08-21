<?php

namespace App\Services;

use App\Models\MinimumWageRule;

class MinimumWageRuleService
{
    public function create(array $data): MinimumWageRule
    {
        return MinimumWageRule::create($data);
    }

    public function update(MinimumWageRule $minimumWageRule, array $data): MinimumWageRule
    {
        $minimumWageRule->update($data);

        return $minimumWageRule->fresh();
    }

    public function delete(MinimumWageRule $minimumWageRule): bool
    {
        return $minimumWageRule->delete();
    }
}
