<?php

namespace App\Http\Requests\Admin;

use App\Models\Employee;
use App\Models\ProjectTeamMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('project_team_member')?->id;

        return [
            'project_id' => 'required|exists:projects,id',
            // One membership row per employee per project — an app-level
            // mirror of the DB's composite unique index, so the admin gets a
            // readable message instead of a raw constraint error.
            'employee_id' => [
                'required',
                'exists:employees,id',
                Rule::unique('project_team_members', 'employee_id')
                    ->where(fn ($q) => $q->where('project_id', $this->project_id))
                    ->ignore($id),
            ],
            'team_role' => ['required', Rule::in(ProjectTeamMember::ROLES)],
            'allocation_percent' => 'required|numeric|min:0.01|max:100',
            'joined_date' => 'required|date',
            'left_date' => 'nullable|date|after_or_equal:joined_date',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.unique' => 'This employee is already on the project team — edit the existing record instead.',
            'left_date.after_or_equal' => 'The leaving date cannot fall before the joining date.',
            'allocation_percent.max' => 'Allocation cannot exceed 100% of an employee\'s time.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // A project has one lead at a time. Only current members count —
            // someone who has already left is not still holding the role.
            if ($this->team_role !== 'lead') {
                return;
            }

            $id = $this->route('project_team_member')?->id;

            $existingLead = ProjectTeamMember::query()
                ->where('project_id', $this->project_id)
                ->where('team_role', 'lead')
                ->current()
                ->when($id, fn ($q) => $q->where('id', '!=', $id))
                ->first();

            if (! $existingLead) {
                return;
            }

            $employee = Employee::find($existingLead->employee_id);
            $name = $employee ? $employee->first_name . ' ' . $employee->last_name : 'another member';

            $validator->errors()->add(
                'team_role',
                'This project already has a team lead (' . $name . '). Change their role or record their leaving date first.'
            );
        });
    }
}
