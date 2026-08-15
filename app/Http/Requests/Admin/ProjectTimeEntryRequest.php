<?php

namespace App\Http\Requests\Admin;

use App\Models\ProjectTask;
use Illuminate\Foundation\Http\FormRequest;

class ProjectTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'project_id' => 'required|exists:projects,id',
            'project_task_id' => 'nullable|exists:project_tasks,id',
            'work_date' => 'required|date',
            'started_at' => 'nullable|date|required_with:ended_at',
            'ended_at' => 'nullable|date|after:started_at|required_with:started_at',
            'hours' => 'nullable|numeric|min:0.01|max:24',
            'is_billable' => 'nullable|boolean',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'started_at.required_with' => 'Enter both a start and an end time, or leave both blank and type the hours instead.',
            'ended_at.required_with' => 'Enter both a start and an end time, or leave both blank and type the hours instead.',
            'ended_at.after' => 'The end time must be after the start time.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Either a real clock-time pair or a manually-typed duration is
            // required — an entry with neither carries no actual duration.
            $hasClockTimes = $this->started_at && $this->ended_at;

            if (! $hasClockTimes && ! $this->hours) {
                $validator->errors()->add(
                    'hours',
                    'Enter either the hours worked, or both a start and an end time.'
                );

                return;
            }

            if (! $this->project_task_id) {
                return;
            }

            // A time entry's task, if any, must belong to the same project —
            // otherwise the project's own hour totals would silently include
            // work logged against a different project's task.
            $task = ProjectTask::find($this->project_task_id);

            if ($task && (int) $task->project_id !== (int) $this->project_id) {
                $validator->errors()->add(
                    'project_task_id',
                    'That task belongs to a different project. Pick a task from the selected project.'
                );
            }
        });
    }
}
