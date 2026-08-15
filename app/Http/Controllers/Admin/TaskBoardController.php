<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Services\ProjectTaskService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskBoardController extends Controller
{
    use ActivityLogger;

    protected $projectTaskService;

    public function __construct(ProjectTaskService $projectTaskService)
    {
        $this->projectTaskService = $projectTaskService;
    }

    /**
     * The board: one column per task state, cards ordered within each column.
     *
     * No new table — this is a second way of looking at `project_tasks`,
     * the same relationship the Sales Pipeline Kanban has to Opportunities.
     */
    public function index(Request $request)
    {
        $query = ProjectTask::active()->with(['project.client', 'milestone', 'assignedTo']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->orderBy('sort_order')->orderBy('id')->get();

        $columns = collect(ProjectTask::STATUSES)->mapWithKeys(function ($status) use ($tasks) {
            return [$status => $tasks->where('task_status', $status)->values()];
        });

        return view('admin.task-board.index', [
            'columns' => $columns,
            'statuses' => ProjectTask::STATUSES,
            'projects' => Project::active()->orderBy('name')->get(),
            'employees' => Employee::active()->orderBy('first_name')->get(),
            'priorities' => ProjectTask::PRIORITIES,
            'totalTasks' => $tasks->count(),
            'overdueTasks' => $tasks->filter(fn ($task) => $task->is_overdue)->count(),
            'unassignedTasks' => $tasks->whereNull('assigned_to')->count(),
        ]);
    }

    /**
     * Drag-and-drop endpoint: move one card to another column and renumber
     * that column. Returns plain JSON — the board updates in place.
     */
    public function move(Request $request, ProjectTask $projectTask)
    {
        $validated = $request->validate([
            'task_status' => ['required', Rule::in(ProjectTask::STATUSES)],
            'ordered_ids' => 'nullable|array',
            'ordered_ids.*' => 'integer',
        ]);

        DB::beginTransaction();

        try {
            $oldStatus = $projectTask->task_status;

            $updatedTask = $this->projectTaskService->moveToStatus(
                $projectTask,
                $validated['task_status'],
                $validated['ordered_ids'] ?? []
            );

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'task-board',
                'action' => 'move',
                'model' => 'ProjectTask',
                'model_id' => $projectTask->id,
                'description' => 'Task "' . $updatedTask->title . ' (' . $updatedTask->task_code . ')" moved from '
                    . ucwords(str_replace('_', ' ', $oldStatus)) . ' to ' . $updatedTask->task_status_label,
                'new_data' => ['task_status' => $updatedTask->task_status],
                'old_data' => ['task_status' => $oldStatus]
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'task_status' => $updatedTask->task_status,
                'progress_percent' => $updatedTask->progress_percent,
                'message' => 'Task moved to ' . $updatedTask->task_status_label . '.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
