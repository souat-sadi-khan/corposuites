<?php

namespace App\Http\Requests\Admin;

use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // task_code is deliberately absent — server-generated only.
            'project_id' => 'required|exists:projects,id',
            'project_milestone_id' => 'nullable|exists:project_milestones,id',
            'title' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:employees,id',
            'priority' => ['required', Rule::in(ProjectTask::PRIORITIES)],
            'task_status' => ['required', Rule::in(ProjectTask::STATUSES)],
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'completed_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0|max:9999',
            'progress_percent' => 'nullable|integer|min:0|max:100',
            'sort_order' => 'nullable|integer|min:1|max:999',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Select the project this task belongs to.',
            'due_date.after_or_equal' => 'The due date cannot fall before the start date.',
            'progress_percent.max' => 'Progress cannot exceed 100%.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty() || ! $this->project_milestone_id) {
                return;
            }

            // A task can only hang off a milestone of its own project —
            // otherwise the milestone view would silently show foreign work.
            $milestone = ProjectMilestone::find($this->project_milestone_id);

            if ($milestone && (int) $milestone->project_id !== (int) $this->project_id) {
                $validator->errors()->add(
                    'project_milestone_id',
                    'That milestone belongs to a different project. Pick a milestone from the selected project.'
                );
            }
        });
    }
}
