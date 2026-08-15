<?php

namespace App\Services;

use App\Models\Experience;

class ExperienceService
{
    public function create(array $data): Experience
    {
        if (!empty($data['is_current'])) {
            $data['end_date'] = null;
        }

        return Experience::create($data);
    }

    public function update(Experience $experience, array $data): Experience
    {
        if (!empty($data['is_current'])) {
            $data['end_date'] = null;
        }

        $experience->update($data);
        return $experience;
    }

    public function delete(Experience $experience): bool
    {
        return $experience->delete();
    }
}
