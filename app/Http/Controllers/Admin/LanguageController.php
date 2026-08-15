<?php

namespace App\Http\Controllers\Admin;

use App\Models\Lang\Language;
use App\Services\LanguageService;
use App\Http\Controllers\Controller;
use App\Models\Lang\TranslationGroup;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class LanguageController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $languages = Language::query()
                ->latest();

            return DataTables::eloquent($languages)

                ->addColumn('language', function ($model) {

                    return '
                        <div class="d-flex align-items-center">

                            <div class="flex-grow-1">

                                <div class="fw-semibold">'
                                    . e($model->name) .
                                '</div>

                                <small class="text-muted">'
                                    . e($model->native_name) .
                                '</small>

                            </div>

                        </div>
                    ';

                })

                ->editColumn('direction', function ($model) {

                    if ($model->direction == 'rtl') {

                        return '<span class="badge bg-warning-subtle text-warning">
                                    RTL
                                </span>';

                    }

                    return '<span class="badge bg-success-subtle text-success">
                                LTR
                            </span>';

                })

                ->editColumn('status', function ($model) {

                    if ($model->is_active) {

                        return '
                            <span class="badge bg-success">
                                Active
                            </span>
                        ';

                    }

                    return '
                        <span class="badge bg-danger">
                            Inactive
                        </span>
                    ';

                })

                ->addColumn('default', function ($model) {

                    if ($model->is_default) {

                        return '
                            <span class="badge bg-primary">
                                Default
                            </span>
                        ';

                    }

                    return '-';

                })

                ->editColumn('updated_at', function ($model) {

                    return $model->updated_at->format('d M Y');

                })

                ->addColumn('action', function ($model) {

                    return view(
                        'admin.languages.action',
                        compact('model')
                    );

                })

                ->rawColumns([
                    'language',
                    'direction',
                    'status',
                    'default',
                    'action'
                ])

                ->make(true);
        }

        return view('admin.languages.index');
    }

    public function create()
    {
        return view('admin.languages.create');
    }

    public function store(
        Request $request,
        LanguageService $service
    ) {

        // Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'native_name' => 'required',
            'code' => 'required|unique:languages,code',
            'flag' => 'nullable',
            'direction' => 'required|in:ltr,rtl',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $service->create($request->only([
            'name',
            'native_name',
            'code',
            'flag',
            'direction',
            'is_active'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Language created successfully',
        ]);

    }

    public function edit(Language $language)
    {
        return view('admin.languages.edit', [
            'model' => $language
        ]);
    }

    public function update(
        Request $request,
        Language $language
    ) {

        // Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'native_name' => 'required',
            'code' => 'required|unique:languages,code,' . $language->id,
            'flag' => 'nullable',
            'direction' => 'required|in:ltr,rtl',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }

        $language->update($request->only([
            'name',
            'native_name',
            'code',
            'flag',
            'direction',
            'is_active'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Language updated successfully',
        ]);
    }

    public function destroy(Language $language)
    {
        if ($language->is_default) {
            return response()->json([
                'status' => false,
                'message' => 'Default language cannot be deleted.',
            ]);
        }

        $language->delete();

        return response()->json([
            'status' => true,
            'message' => 'Language deleted successfully.',
        ]);
    }

    public function deleteSelected(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:languages,id'
        ]);

        try {
            $ids = $request->input('ids');

            Language::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => count($ids) . ' languages deleted successfully.'
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while trying to delete the items.'
            ], 500);
        }
    }

    public function translate(Language $language)
    {
        $translations = $language->translations()
            ->with('translationKey.group')
            ->orderBy('translation_key_id')
            ->get();

        $groups = TranslationGroup::orderBy('name')->get();

        return view(
            'admin.languages.translation',
            compact(
                'language',
                'translations',
                'groups'
            )
        );
    }

    public function updateTranslation(Request $request, LanguageService $service, Language $language)
    {
        foreach ($request->translations as $id => $value) {
            $language->translations()
                ->where('id', $id)
                ->update([
                    'value' => $value
                ]);
        }

        // Cache Update
        $service->refresh($language);

        return response()->json([
            'status' => true,
            'message' => 'Translations updated successfully.',
            'goto' => route('admin.languages.index')
        ]);
    }

    public function changeLanguage(Request $request)
    {
        $request->validate([
            'language' => ['required', 'exists:languages,code'],
        ]);

        $language = Language::where('code', $request->language)->where('is_active', true)->firstOrFail();

        // Remove previous default
        Language::query()->update([
            'is_default' => 0
        ]);

        // Set new default
        $language->update([
            'is_default' => 1
        ]);

        session([
            'language' => $language->code,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Language changed successfully.',
            'language' => $language->code,
        ]);
    }
}
