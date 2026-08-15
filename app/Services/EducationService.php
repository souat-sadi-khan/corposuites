<?php

namespace App\Services;

use App\Models\Education;

class EducationService
{
    public function create(array $data): Education
    {
        return Education::create($data);
    }

    public function update(Education $education, array $data): Education
    {
        $education->update($data);
        return $education;
    }

    public function delete(Education $education): bool
    {
        return $education->delete();
    }
}
