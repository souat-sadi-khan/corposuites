<?php

namespace App\Http\Requests\Admin;

use App\Models\ProjectMilestone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('project_milestone')?->id;

        return [
            'project_id' => 'required|exists:projects,id',
            // Names are unique within their own project only — two projects
            // may both have a "Phase 1".
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('project_milestones', 'name')
                    ->where(fn ($q) => $q->where('project_id', $this->project_id))
                    ->ignore($id),
            ],
            'sort_order' => 'nullable|integer|min:1|max:999',
            'due_date' => 'required|date',
            'completed_date' => 'nullable|date',
            'milestone_status' => ['required', Rule::in(ProjectMilestone::STATUSES)],
            'assigned_to' => 'nullable|exists:employees,id',
            'deliverables' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Select the project this milestone belongs to.',
            'name.unique' => 'This project already has a milestone with that name.',
        ];
    }
}
