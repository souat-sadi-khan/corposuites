<?php

namespace App\Http\Controllers\Admin\DMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DMS\BulkIdsRequest;
use App\Http\Requests\Admin\DMS\MoveItemsRequest;
use App\Http\Requests\Admin\DMS\RenameItemRequest;
use App\Http\Requests\Admin\DMS\StoreFolderRequest;
use App\Http\Requests\Admin\DMS\UploadFilesRequest;
use App\Models\Admin\DMS\DmsItem;
use App\Services\Admin\DMS\DmsService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Base Admin Panel feature — Document Management System (DMS).
 *
 * Hardcoded into the sidebar (see resources/views/admin/layout/partials/sidebar.blade.php)
 * rather than registered through the dynamic Module/ModuleMenu system, since
 * this is meant to be reusable infrastructure across projects, not a
 * per-project ERP module.
 *
 * The page itself (resources/views/admin/dms/index.blade.php) is a single
 * AJAX-driven screen — every list/mutation action here returns JSON and the
 * frontend (public/assets/system/js/pages/dms.js) re-renders from that,
 * rather than following the DataTables + remote-modal pattern used by the
 * rest of the admin panel.
 */
class DmsController extends Controller
{
    use ActivityLogger;

    public function __construct(protected DmsService $dmsService)
    {
    }

    public function index()
    {
        return view('admin.dms.index', ['title' => 'Document Management']);
    }

    public function data(Request $request)
    {
        $result = $this->dmsService->list([
            'scope' => $request->string('scope', 'all')->toString(),
            'folder_id' => $request->filled('folder_id') ? (int) $request->input('folder_id') : null,
            'q' => $request->input('q'),
            'types' => $request->input('types', []),
            'sort' => $request->string('sort', 'name-asc')->toString(),
            'page' => $request->integer('page', 1),
            'per_page' => $request->integer('per_page', 24),
        ]);

        return response()->json([
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'counts' => $this->dmsService->counts(),
            'breadcrumb' => $this->dmsService->breadcrumb($request->filled('folder_id') ? (int) $request->input('folder_id') : null),
        ]);
    }

    public function folderTree(Request $request)
    {
        $exclude = array_map('intval', (array) $request->input('exclude', []));

        return response()->json(['tree' => $this->dmsService->folderTree($exclude)]);
    }

    /**
     * GET form for the "New Folder" remote modal (opened via the shared
     * #openModal system, the same one Roles/etc. use), loaded fresh on every
     * click so its hidden parent_id reflects whatever folder is currently
     * open client-side.
     */
    public function createFolderForm(Request $request)
    {
        $parentId = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;
        $parent = $parentId ? DmsItem::find($parentId) : null;

        return view('admin.dms.folder-create', ['parentId' => $parentId, 'parent' => $parent]);
    }

    public function storeFolder(StoreFolderRequest $request)
    {
        $item = $this->dmsService->createFolder(
            $request->integer('parent_id') ?: null,
            $request->string('name')->toString(),
            auth()->guard('admin')->id()
        );

        $this->logActivity([
            'module' => 'dms',
            'action' => 'create',
            'model' => DmsItem::class,
            'model_id' => $item->id,
            'description' => 'Created folder "' . $item->name . '"',
            'new_data' => $item->toArray(),
        ]);

        // {status: true, ...} matches the shape the shared .ajax-form submit
        // handler in main.js expects (see roles/create.blade.php's own form).
        return response()->json(['status' => true, 'message' => 'Folder created', 'item' => $this->dmsService->toArray($item)]);
    }

    public function upload(UploadFilesRequest $request)
    {
        $parentId = $request->integer('parent_id') ?: null;
        $adminId = auth()->guard('admin')->id();
        $created = [];

        foreach ($request->file('files', []) as $file) {
            $item = $this->dmsService->upload($file, $parentId, $adminId);
            $created[] = $this->dmsService->toArray($item);
        }

        $this->logActivity([
            'module' => 'dms',
            'action' => 'upload',
            'model' => DmsItem::class,
            'description' => count($created) . ' file(s) uploaded',
        ]);

        return response()->json(['message' => count($created) . ' file(s) uploaded', 'items' => $created]);
    }

    public function rename(RenameItemRequest $request, DmsItem $dms)
    {
        $oldName = $dms->name;
        $item = $this->dmsService->rename($dms, $request->string('name')->toString());

        $this->logActivity([
            'module' => 'dms',
            'action' => 'rename',
            'model' => DmsItem::class,
            'model_id' => $item->id,
            'description' => 'Renamed "' . $oldName . '" to "' . $item->name . '"',
        ]);

        return response()->json(['message' => 'Renamed', 'item' => $this->dmsService->toArray($item)]);
    }

    public function toggleFavorite(DmsItem $dms)
    {
        $item = $this->dmsService->toggleFavorite($dms);

        return response()->json(['message' => $item->favorite ? 'Added to favorites' : 'Removed from favorites', 'item' => $this->dmsService->toArray($item)]);
    }

    public function download(DmsItem $dms)
    {
        abort_if($dms->kind !== DmsItem::KIND_FILE || !$dms->path, 404);
        abort_unless(Storage::disk($dms->disk ?: 'public')->exists($dms->path), 404);

        return Storage::disk($dms->disk ?: 'public')->download($dms->path, $dms->name);
    }

    public function bulkTrash(BulkIdsRequest $request)
    {
        $count = $this->dmsService->moveToTrash($request->input('ids'));

        $this->logActivity([
            'module' => 'dms',
            'action' => 'trash',
            'description' => $count . ' item(s) moved to trash',
        ]);

        return response()->json(['message' => $count . ' item(s) moved to trash', 'count' => $count]);
    }

    public function bulkRestore(BulkIdsRequest $request)
    {
        $count = $this->dmsService->restore($request->input('ids'));

        $this->logActivity([
            'module' => 'dms',
            'action' => 'restore',
            'description' => $count . ' item(s) restored',
        ]);

        return response()->json(['message' => $count . ' item(s) restored', 'count' => $count]);
    }

    public function bulkDeleteForever(BulkIdsRequest $request)
    {
        $count = $this->dmsService->deleteForever($request->input('ids'));

        $this->logActivity([
            'module' => 'dms',
            'action' => 'delete',
            'description' => $count . ' item(s) permanently deleted',
        ]);

        return response()->json(['message' => $count . ' item(s) permanently deleted', 'count' => $count]);
    }

    public function emptyTrash()
    {
        $count = $this->dmsService->emptyTrash();

        $this->logActivity([
            'module' => 'dms',
            'action' => 'empty-trash',
            'description' => 'Trash emptied (' . $count . ' item(s))',
        ]);

        return response()->json(['message' => 'Trash emptied', 'count' => $count]);
    }

    public function bulkMove(MoveItemsRequest $request)
    {
        $count = $this->dmsService->move($request->input('ids'), $request->integer('destination_id') ?: null);

        $this->logActivity([
            'module' => 'dms',
            'action' => 'move',
            'description' => $count . ' item(s) moved',
        ]);

        return response()->json(['message' => $count . ' item(s) moved', 'count' => $count]);
    }

    public function bulkDownload(BulkIdsRequest $request)
    {
        $zipPath = $this->dmsService->zipForDownload($request->input('ids'));

        return response()->download($zipPath, 'files.zip')->deleteFileAfterSend(true);
    }
}
