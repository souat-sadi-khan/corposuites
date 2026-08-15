<?php

namespace App\Services;

use App\Models\Skill;

class SkillService
{
    public function create(array $data): Skill
    {
        return Skill::create($data);
    }

    public function update(Skill $skill, array $data): Skill
    {
        $skill->update($data);
        return $skill;
    }

    public function delete(Skill $skill): bool
    {
        return $skill->delete();
    }
}
