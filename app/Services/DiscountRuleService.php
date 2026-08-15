<?php

namespace App\Services;

use App\Models\DiscountRule;

class DiscountRuleService
{
    public function create(array $data): DiscountRule
    {
        return DiscountRule::create($data);
    }

    public function update(DiscountRule $discountRule, array $data): DiscountRule
    {
        $discountRule->update($data);
        return $discountRule;
    }

    public function delete(DiscountRule $discountRule): bool
    {
        return $discountRule->delete();
    }
}
