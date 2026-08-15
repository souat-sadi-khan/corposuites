<?php

namespace App\Services;

use App\Models\ProjectTaskDependency;

class ProjectTaskDependencyService
{
    public function create(array $data): ProjectTaskDependency
    {
        return ProjectTaskDependency::create($data);
    }

    public function update(ProjectTaskDependency $dependency, array $data): ProjectTaskDependency
    {
        $dependency->update($data);

        return $dependency->fresh();
    }

    public function delete(ProjectTaskDependency $dependency): bool
    {
        return $dependency->delete();
    }
}
