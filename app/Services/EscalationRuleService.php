<?php

namespace App\Services;

use App\Models\EscalationRule;

class EscalationRuleService
{
    public function create(array $data): EscalationRule
    {
        return EscalationRule::create($data);
    }

    public function update(EscalationRule $escalationRule, array $data): EscalationRule
    {
        $escalationRule->update($data);

        return $escalationRule->fresh();
    }

    public function delete(EscalationRule $escalationRule): bool
    {
        return $escalationRule->delete();
    }
}
