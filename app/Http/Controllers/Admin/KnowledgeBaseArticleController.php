<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KnowledgeBaseArticleRequest;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\TicketCategory;
use App\Services\KnowledgeBaseArticleService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class KnowledgeBaseArticleController extends Controller
{
    use ActivityLogger;

    protected $knowledgeBaseArticleService;

    public function __construct(KnowledgeBaseArticleService $knowledgeBaseArticleService)
    {
        $this->knowledgeBaseArticleService = $knowledgeBaseArticleService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = KnowledgeBaseArticle::with(['category', 'ticketCategory', 'author']);

            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            if ($request->knowledge_base_category_id) {
                $query->where('knowledge_base_category_id', $request->knowledge_base_category_id);
            }

            if ($request->article_status) {
                $query->where('article_status', $request->article_status);
            }

            if ($request->visibility) {
                $query->where('visibility', $request->visibility);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('title_col', function ($row) {
                    return '<div class="fw-semibold">' . e($row->title) . '</div><small class="text-muted">/' . e($row->slug) . '</small>';
                })
                ->addColumn('category_name', function ($row) {
                    return $row->category
                        ? e($row->category->name)
                        : '<span class="text-danger">Uncategorised</span>';
                })
                ->addColumn('ticket_category_name', function ($row) {
                    return $row->ticketCategory ? e($row->ticketCategory->name) : '<span class="text-muted">-</span>';
                })
                ->addColumn('author_name', function ($row) {
                    return $row->author ? e($row->author->name) : '<span class="text-muted">-</span>';
                })
                ->addColumn('visibility_badge', function ($row) {
                    $color = $row->visibility === 'public' ? 'info' : 'secondary';
                    return '<span class="badge bg-' . $color . '">' . $row->visibility_label . '</span>';
                })
                ->addColumn('article_status_badge', function ($row) {
                    $colors = ['draft' => 'secondary', 'published' => 'success', 'archived' => 'dark'];
                    $color = $colors[$row->article_status] ?? 'secondary';
                    $html = '<span class="badge bg-' . $color . '">' . $row->article_status_label . '</span>';
                    if ($row->article_status === 'published' && $row->published_at) {
                        $html .= '<br><small class="text-muted">' . $row->published_at->format('d M Y') . '</small>';
                    }
                    return $html;
                })
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.knowledge-base-articles.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.knowledge-base-articles.action', compact('row'))->render();
                })
                ->rawColumns(['title_col', 'category_name', 'ticket_category_name', 'author_name', 'visibility_badge', 'article_status_badge', 'status_badge', 'action'])
                ->make(true);
        }

        return view('admin.knowledge-base-articles.index', $this->formData());
    }

    public function create()
    {
        return view('admin.knowledge-base-articles.create', $this->formData());
    }

    public function store(KnowledgeBaseArticleRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['authored_by'] = auth()->guard('admin')->id();

            $article = $this->knowledgeBaseArticleService->create($data);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'knowledge-base-articles',
                'action' => 'create',
                'model' => 'KnowledgeBaseArticle',
                'model_id' => $article->id,
                'description' => 'Knowledge Base Article "' . $article->title . '" created',
                'new_data' => $article->toArray(),
                'old_data' => null,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Knowledge base article created successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function edit(KnowledgeBaseArticle $knowledgeBaseArticle)
    {
        return view('admin.knowledge-base-articles.edit', array_merge(
            ['knowledgeBaseArticle' => $knowledgeBaseArticle],
            $this->formData()
        ));
    }

    public function update(KnowledgeBaseArticleRequest $request, KnowledgeBaseArticle $knowledgeBaseArticle)
    {
        DB::beginTransaction();

        try {
            $oldData = $knowledgeBaseArticle->toArray();
            $updated = $this->knowledgeBaseArticleService->update($knowledgeBaseArticle, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'knowledge-base-articles',
                'action' => 'update',
                'model' => 'KnowledgeBaseArticle',
                'model_id' => $knowledgeBaseArticle->id,
                'description' => 'Knowledge Base Article "' . $updated->title . '" updated',
                'new_data' => $updated->toArray(),
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.knowledge-base-articles.index'),
                'message' => 'Knowledge base article updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(KnowledgeBaseArticle $knowledgeBaseArticle)
    {
        DB::beginTransaction();

        try {
            $oldData = $knowledgeBaseArticle->toArray();

            $this->knowledgeBaseArticleService->delete($knowledgeBaseArticle);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'knowledge-base-articles',
                'action' => 'delete',
                'model' => 'KnowledgeBaseArticle',
                'model_id' => $oldData['id'],
                'description' => 'Knowledge Base Article "' . $oldData['title'] . '" deleted',
                'new_data' => null,
                'old_data' => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Knowledge base article deleted successfully.',
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

        $model = KnowledgeBaseArticle::find($id);
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

    protected function formData(): array
    {
        return [
            'knowledgeBaseCategories' => KnowledgeBaseCategory::active()->orderBy('name')->get(),
            'ticketCategories' => TicketCategory::active()->orderBy('name')->get(),
        ];
    }
}
