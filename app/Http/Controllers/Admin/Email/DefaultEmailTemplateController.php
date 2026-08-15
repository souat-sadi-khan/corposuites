<?php

namespace App\Http\Controllers\Admin\Email;

use App\Http\Controllers\Controller;
use App\Models\Email\DefaultEmailTemplate;
use App\Services\Email\DefaultEmailTemplateService;
use App\Services\Email\TemplatePreviewService;
use App\Services\Email\TemplateRendererService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class DefaultEmailTemplateController extends Controller
{
    protected DefaultEmailTemplateService $templateService;
    protected TemplateRendererService $rendererService;
    protected TemplatePreviewService $previewService;

    public function __construct(
        DefaultEmailTemplateService $templateService,
        TemplateRendererService $rendererService,
        TemplatePreviewService $previewService
    ) {
        $this->templateService = $templateService;
        $this->rendererService = $rendererService;
        $this->previewService = $previewService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DefaultEmailTemplate::query();

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('key', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            }

            if ($request->status !== null && $request->status !== '') {
                $query->where('status', $request->status);
            }

            if ($request->category) {
                $query->where('category', $request->category);
            }

            $query->orderBy('sort_order')->orderBy('id', 'desc');

            return DataTables::eloquent($query)
                ->editColumn('status', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.email.email-templates.change-status', $row->id) . '" class="switch form-control-sm form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('variables_display', function ($row) {
                    $vars = $row->variables;
                    if (is_string($vars)) {
                        $vars = json_decode($vars, true);
                    }
                    $vars = $vars ?? [];
                    if (empty($vars)) return '-';
                    return '<code>{{ ' . implode(' }}</code>, <code>{{ ', $vars) . ' }}</code>';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.email-templates.action', compact('row'))->render();
                })
                ->rawColumns(['status', 'variables_display', 'action'])
                ->make(true);
        }

        return view('admin.email-templates.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.email-templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Convert variables from textarea to array
        $request->merge([
            'variables' => collect(
                preg_split("/\r\n|\n|\r/", $request->variables ?? '')
            )
            ->map(fn($v) => trim($v))
            ->filter()
            ->values()
            ->toArray(),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'key' => 'nullable|string|max:100|unique:default_email_templates,key',
            'category' => 'nullable|string|max:100',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:50',
            'description' => 'nullable|string',
            'is_system' => 'nullable|boolean',
            'status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $data = $request->only([
            'name', 'key', 'category', 'subject', 'body', 'description',
            'is_system', 'status', 'sort_order'
        ]);

        // Convert variables to array if provided
        if ($request->has('variables')) {
            $data['variables'] = array_filter($request->variables); // remove empty strings
        }

        $template = $this->templateService->create($data);

        // Log activity (optional)
        // $this->logActivity(...)

        return response()->json([
            'status' => true,
            'message' => 'Email template created successfully.',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $template = $this->templateService->getById($id);
        if (!$template) {
            abort(404);
        }
        return view('admin.email-templates.edit', compact('template'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Convert variables from textarea to array
        $request->merge([
            'variables' => collect(
                preg_split("/\r\n|\n|\r/", $request->variables ?? '')
            )
            ->map(fn($v) => trim($v))
            ->filter()
            ->values()
            ->toArray(),
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'key' => 'nullable|string|max:100|unique:default_email_templates,key,' . $id,
            'category' => 'nullable|string|max:100',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:50',
            'description' => 'nullable|string',
            'is_system' => 'nullable|boolean',
            'status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $data = $request->only([
            'name', 'key', 'category', 'subject', 'body', 'description',
            'is_system', 'status', 'sort_order'
        ]);

        if ($request->has('variables')) {
            $data['variables'] = array_filter($request->variables);
        }

        $this->templateService->update($id, $data);

        return response()->json([
            'status' => true,
            'message' => 'Email template updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $template = $this->templateService->getById($id);
        if (!$template) {
            return response()->json([
                'status' => false,
                'message' => 'Template not found.'
            ]);
        }

        // Prevent deletion of system templates if needed
        if ($template->is_system) {
            return response()->json([
                'status' => false,
                'message' => 'System templates cannot be deleted.'
            ]);
        }

        $this->templateService->delete($id);

        return response()->json([
            'status' => true,
            'message' => 'Template deleted successfully.'
        ]);
    }

    /**
     * Preview the template with dummy data.
     */
    public function preview(string $id)
    {
        $template = $this->templateService->getById($id);
        if (!$template) {
            abort(404);
        }

        $preview = $this->previewService->preview($template);

        return view('admin.email-templates.preview-modal', compact('template', 'preview'));
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(string $id)
    {
        $template = $this->templateService->getById($id);
        if (!$template) {
            return response()->json([
                'status' => false,
                'message' => 'Template not found.'
            ]);
        }

        $newTemplate = $this->templateService->duplicate($id);

        return response()->json([
            'status' => true,
            'message' => 'Template duplicated successfully.'
        ]);
    }

    /**
     * Change the status of a template (AJAX toggle).
     */
    public function changeStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $template = $this->templateService->getById($id);
        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found.'
            ]);
        }

        $this->templateService->changeStatus($id, $request->status);

        return response()->json([
            'success' => true,
            'message' => 'Template status updated.'
        ]);
    }
}
