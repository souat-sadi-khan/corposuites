<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KnowledgeBaseCategoryRequest;
use App\Models\KnowledgeBaseCategory;
use App\Services\KnowledgeBaseCategoryService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class KnowledgeBaseCategoryController extends Controller
{
    use ActivityLogger;

    protected $knowledgeBaseCategoryService;

    public function __construct(KnowledgeBaseCategoryService $knowledgeBaseCategoryService)
    {
        $this->knowledgeBaseCategoryService = $knowledgeBaseCategoryService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = KnowledgeBaseCategory::withCount('articles');

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('articles_count_label', function ($row) {
                    return $row->articles_count . ' article' . ($row->articles_count === 1 ? '' : 's');
                })
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.knowledge-base-categories.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.knowledge-base-categories.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('admin.knowledge-base-categories.index');
    }

    public function create()
    {
        return view('admin.knowledge-base-categories.create');
    }

    public function store(KnowledgeBaseCategoryRequest $request)
    {
        DB::beginTransaction();

        try {
            $knowledgeBaseCategory = $this->knowledgeBaseCategoryService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'knowledge-base-categories',
                'action' => 'create',
                'model' => 'KnowledgeBaseCategory',
                'model_id' => $knowledgeBaseCategory->id,
                'description' => 'Knowledge Base Category "' . $knowledgeBaseCategory->name . '" created',
                'new_data' => $knowledgeBaseCategory->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Knowledge base category created successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function edit(KnowledgeBaseCategory $knowledgeBaseCategory)
    {
        return view('admin.knowledge-base-categories.edit', compact('knowledgeBaseCategory'));
    }

    public function update(KnowledgeBaseCategoryRequest $request, KnowledgeBaseCategory $knowledgeBaseCategory)
    {
        DB::beginTransaction();

        try {
            $oldData = $knowledgeBaseCategory->toArray();
            $updated = $this->knowledgeBaseCategoryService->update($knowledgeBaseCategory, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'knowledge-base-categories',
                'action' => 'update',
                'model' => 'KnowledgeBaseCategory',
                'model_id' => $knowledgeBaseCategory->id,
                'description' => 'Knowledge Base Category "' . $updated->name . '" updated',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.knowledge-base-categories.index'),
                'message' => 'Knowledge base category updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(KnowledgeBaseCategory $knowledgeBaseCategory)
    {
        DB::beginTransaction();

        try {
            $oldData = $knowledgeBaseCategory->toArray();

            $this->knowledgeBaseCategoryService->delete($knowledgeBaseCategory);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'knowledge-base-categories',
                'action' => 'delete',
                'model' => 'KnowledgeBaseCategory',
                'model_id' => $oldData['id'],
                'description' => 'Knowledge Base Category "' . $oldData['name'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Knowledge base category deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = KnowledgeBaseCategory::find($id);
        if (! $model) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.',
            ]);
        }

        $model->status = $request->input('status');
        $model->save();

        return response()->json([
            'success' => true,
            'message' => 'Record status updated successfully.',
        ]);
    }
}
