<?php

namespace App\Http\Requests\Admin;

use App\Models\ProjectTask;
use App\Models\ProjectTaskDependency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectTaskDependencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('project_task_dependency')?->id;

        return [
            'task_id' => ['required', 'exists:project_tasks,id', 'different:depends_on_task_id'],
            'depends_on_task_id' => [
                'required',
                'exists:project_tasks,id',
                Rule::unique('project_task_dependencies', 'depends_on_task_id')
                    ->where(fn ($query) => $query->where('task_id', $this->task_id))
                    ->ignore($id),
            ],
            'dependency_type' => ['required', Rule::in(ProjectTaskDependency::DEPENDENCY_TYPES)],
            'lag_days' => 'nullable|integer|min:-365|max:365',
            'notes' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'task_id.different' => 'A task cannot depend on itself.',
            'depends_on_task_id.unique' => 'This task already depends on that predecessor.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty() || ! $this->task_id || ! $this->depends_on_task_id) {
                return;
            }

            $task = ProjectTask::find($this->task_id);
            $dependsOnTask = ProjectTask::find($this->depends_on_task_id);

            if (! $task || ! $dependsOnTask) {
                return;
            }

            // Both ends of a dependency must live on the same schedule —
            // otherwise the Gantt/board views would show a cross-project link
            // that means nothing on either timeline.
            if ((int) $task->project_id !== (int) $dependsOnTask->project_id) {
                $validator->errors()->add(
                    'depends_on_task_id',
                    'Both tasks must belong to the same project.'
                );

                return;
            }

            $id = $this->route('project_task_dependency')?->id;

            if ($this->wouldCreateCycle((int) $this->depends_on_task_id, (int) $this->task_id, $id)) {
                $validator->errors()->add(
                    'depends_on_task_id',
                    'This would create a circular dependency — "' . $dependsOnTask->title . '" already depends on "' . $task->title . '", directly or through another task.'
                );
            }
        });
    }

    /**
     * Does $startTaskId already (transitively) depend on $targetTaskId?
     *
     * Adding a "task_id depends on depends_on_task_id" edge cycles exactly
     * when depends_on_task_id already depends — directly or through a chain
     * of other tasks — on task_id. So this walks the existing "depends on"
     * edges starting at $startTaskId (the new predecessor) and checks
     * whether $targetTaskId (the new dependent) is reachable.
     */
    protected function wouldCreateCycle(int $startTaskId, int $targetTaskId, ?int $ignoreDependencyId = null): bool
    {
        $visited = [];
        $frontier = [$startTaskId];

        while (! empty($frontier)) {
            $node = array_pop($frontier);

            if ($node === $targetTaskId) {
                return true;
            }

            if (isset($visited[$node])) {
                continue;
            }

            $visited[$node] = true;

            $query = ProjectTaskDependency::where('task_id', $node);

            if ($ignoreDependencyId) {
                $query->where('id', '!=', $ignoreDependencyId);
            }

            foreach ($query->pluck('depends_on_task_id') as $next) {
                if (! isset($visited[$next])) {
                    $frontier[] = (int) $next;
                }
            }
        }

        return false;
    }
}
