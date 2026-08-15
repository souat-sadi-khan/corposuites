<?php

namespace App\Services;

use App\Models\Project;

class ProjectService
{
    public function create(array $data): Project
    {
        $data['project_code'] = $this->generateProjectCode();

        return Project::create($this->withDerivedFields($data));
    }

    public function update(Project $project, array $data): Project
    {
        // Issued once; every task, timesheet and invoice filed under this
        // project will reference it, so it must never be re-issued.
        unset($data['project_code']);

        $project->update($this->withDerivedFields($data, $project));

        return $project->fresh();
    }

    public function delete(Project $project): bool
    {
        return $project->delete();
    }

    /**
     * A completed project is 100% done and has an actual end date; if the
     * user did not supply one, today is the honest default. Moving a project
     * back off "completed" clears the actual end date again, so the field can
     * never claim a project finished on a day it did not.
     */
    protected function withDerivedFields(array $data, ?Project $project = null): array
    {
        $status = $data['project_status'] ?? $project?->project_status;

        if ($status === 'completed') {
            $data['progress_percent'] = 100;
            $data['actual_end_date'] = $data['actual_end_date']
                ?? $project?->actual_end_date?->toDateString()
                ?? now()->toDateString();
        } elseif ($project && $project->project_status === 'completed') {
            $data['actual_end_date'] = null;
        }

        return $data;
    }

    protected function generateProjectCode(): string
    {
        $lastId = Project::max('id') ?? 0;

        return 'PRJ-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
