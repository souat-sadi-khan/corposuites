<?php

namespace App\Services\Admin\DMS;

use App\Models\Admin\DMS\DmsItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DmsService
{
    /**
     * Build the filtered/sorted/paginated item list for one tab (scope).
     *
     * scope: all | recent | favorite | trash
     * folder_id: only respected when scope = all (folder navigation)
     */
    public function list(array $filters): array
    {
        $scope = $filters['scope'] ?? 'all';
        $folderId = $filters['folder_id'] ?? null;
        $query = trim((string) ($filters['q'] ?? ''));
        $types = array_values(array_filter((array) ($filters['types'] ?? [])));
        $sort = $filters['sort'] ?? 'name-asc';
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 24)));

        $base = DmsItem::query()->withCount('activeChildren');

        if ($scope === 'trash') {
            $base->where('trashed', true);
        } else {
            $base->where('trashed', false);

            if ($scope === 'favorite') {
                $base->where('favorite', true);
            } elseif ($scope === 'recent') {
                $base->files()->where('updated_at', '>=', now()->subDays(DmsItem::RECENT_DAYS));
            } else {
                $base->where('parent_id', $folderId);
            }
        }

        if ($query !== '') {
            $base->where('name', 'like', '%' . $query . '%');
        }

        if (!empty($types)) {
            $base->where(function ($q) use ($types) {
                $q->where('kind', DmsItem::KIND_FOLDER)->orWhereIn('type', $types);
            });
        }

        [$key, $direction] = array_pad(explode('-', $sort, 2), 2, 'asc');
        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $column = match ($key) {
            'size' => 'size',
            'date' => 'updated_at',
            default => 'name',
        };

        // Folders always sort before files, then by the chosen key.
        $base->orderByRaw("kind = 'folder' desc")->orderBy($column, $direction);

        $total = (clone $base)->count();
        $items = $base->forPage($page, $perPage)->get();

        return [
            'items' => $items->map(fn (DmsItem $item) => $this->toArray($item))->all(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function counts(): array
    {
        return [
            'all' => DmsItem::notTrashed()->count(),
            'recent' => DmsItem::notTrashed()->files()->where('updated_at', '>=', now()->subDays(DmsItem::RECENT_DAYS))->count(),
            'favorite' => DmsItem::notTrashed()->where('favorite', true)->count(),
            'trash' => DmsItem::where('trashed', true)->count(),
        ];
    }

    public function breadcrumb(?int $folderId): array
    {
        $path = [];
        $currentId = $folderId;

        while ($currentId !== null) {
            $folder = DmsItem::find($currentId);
            if (!$folder) {
                break;
            }
            array_unshift($path, ['id' => $folder->id, 'name' => $folder->name]);
            $currentId = $folder->parent_id;
        }

        return $path;
    }

    public function createFolder(?int $parentId, string $name, ?int $adminId): DmsItem
    {
        return DmsItem::create([
            'kind' => DmsItem::KIND_FOLDER,
            'type' => DmsItem::KIND_FOLDER,
            'name' => trim($name),
            'parent_id' => $parentId,
            'created_by' => $adminId,
        ]);
    }

    public function upload(UploadedFile $file, ?int $parentId, ?int $adminId): DmsItem
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());
        $type = DmsItem::typeFromExtension($extension);
        $storedName = (string) Str::uuid() . ($extension ? '.' . $extension : '');
        $path = $file->storeAs($this->uploadDirectory(), $storedName, 'public');

        return DmsItem::create([
            'kind' => DmsItem::KIND_FILE,
            'type' => $type,
            'name' => $file->getClientOriginalName(),
            'parent_id' => $parentId,
            'disk' => 'public',
            'path' => $path,
            'extension' => $extension,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'created_by' => $adminId,
        ]);
    }

    protected function uploadDirectory(): string
    {
        return 'dms/' . now()->format('Y/m');
    }

    public function rename(DmsItem $item, string $name): DmsItem
    {
        $item->update(['name' => trim($name)]);

        return $item;
    }

    public function toggleFavorite(DmsItem $item): DmsItem
    {
        $item->update(['favorite' => !$item->favorite]);

        return $item;
    }

    /**
     * Every id of every item nested (at any depth) under the given folder id.
     * Used to cascade trash/restore/delete/move-cycle checks onto a folder's
     * whole subtree rather than leaving orphaned or half-trashed contents.
     */
    public function descendantIds(int $folderId): array
    {
        $ids = [];
        $queue = [$folderId];

        while (!empty($queue)) {
            $batch = DmsItem::whereIn('parent_id', $queue)->pluck('id')->all();
            if (empty($batch)) {
                break;
            }
            $ids = array_merge($ids, $batch);
            $queue = $batch;
        }

        return $ids;
    }

    protected function expandWithDescendants(array $ids): array
    {
        $all = $ids;

        foreach (DmsItem::whereIn('id', $ids)->folders()->pluck('id') as $folderId) {
            $all = array_merge($all, $this->descendantIds($folderId));
        }

        return array_values(array_unique($all));
    }

    public function moveToTrash(array $ids): int
    {
        $ids = $this->expandWithDescendants($ids);

        return DmsItem::whereIn('id', $ids)->update(['trashed' => true, 'trashed_at' => now()]);
    }

    /**
     * Restoring a folder also restores its whole subtree, symmetric with
     * moveToTrash(). Known limitation: restoring a single file whose parent
     * folder is still trashed leaves it unreachable via normal navigation
     * (its parent_id still points at a hidden folder) — it remains a real,
     * un-trashed row, just not visible until that folder is restored too.
     */
    public function restore(array $ids): int
    {
        $ids = $this->expandWithDescendants($ids);

        return DmsItem::whereIn('id', $ids)->update(['trashed' => false, 'trashed_at' => null]);
    }

    public function deleteForever(array $ids): int
    {
        $ids = $this->expandWithDescendants($ids);

        foreach (DmsItem::whereIn('id', $ids)->files()->get() as $item) {
            if ($item->path) {
                Storage::disk($item->disk ?: 'public')->delete($item->path);
            }
        }

        return DmsItem::whereIn('id', $ids)->delete();
    }

    public function emptyTrash(): int
    {
        $ids = DmsItem::where('trashed', true)->pluck('id')->all();

        return empty($ids) ? 0 : $this->deleteForever($ids);
    }

    /**
     * Move a set of items to a new parent folder (null = root). Silently
     * skips (rather than aborting the whole batch) any item that would move
     * into itself or into its own descendant, since that's a cycle a real
     * filesystem can't represent.
     */
    public function move(array $ids, ?int $destinationId): int
    {
        if ($destinationId !== null) {
            DmsItem::where('id', $destinationId)->folders()->where('trashed', false)->firstOrFail();
        }

        $moved = 0;

        foreach (DmsItem::whereIn('id', $ids)->get() as $item) {
            if ($destinationId !== null) {
                if ($item->id === $destinationId) {
                    continue;
                }
                if ($item->is_folder && in_array($destinationId, $this->descendantIds($item->id), true)) {
                    continue;
                }
            }

            $item->update(['parent_id' => $destinationId]);
            $moved++;
        }

        return $moved;
    }

    /**
     * Nested folder tree for the "Move to" picker. $excludeIds (the items
     * being moved) and all of their descendants are stripped out so the
     * picker can never present an invalid, cycle-forming destination.
     */
    public function folderTree(array $excludeIds = []): array
    {
        $blocked = $this->expandWithDescendants(array_values(array_filter($excludeIds)));

        $folders = DmsItem::folders()
            ->where('trashed', false)
            ->when(!empty($blocked), fn ($q) => $q->whereNotIn('id', $blocked))
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return $this->buildTree($folders, null);
    }

    protected function buildTree($folders, ?int $parentId): array
    {
        return $folders->where('parent_id', $parentId)->map(fn (DmsItem $folder) => [
            'id' => $folder->id,
            'name' => $folder->name,
            'children' => $this->buildTree($folders, $folder->id),
        ])->values()->all();
    }

    /**
     * Build a temporary zip of every file among the given ids (folders are
     * expanded to their files recursively). Caller is responsible for
     * streaming and deleting the returned path.
     */
    public function zipForDownload(array $ids): string
    {
        $ids = $this->expandWithDescendants($ids);

        $items = DmsItem::whereIn('id', $ids)->files()->where('trashed', false)->get();

        $zipDirectory = storage_path('app/temp');
        if (!is_dir($zipDirectory)) {
            mkdir($zipDirectory, 0755, true);
        }

        $zipPath = $zipDirectory . '/dms-' . Str::uuid() . '.zip';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $usedNames = [];
        foreach ($items as $item) {
            if (!$item->path || !Storage::disk($item->disk ?: 'public')->exists($item->path)) {
                continue;
            }

            $fullPath = Storage::disk($item->disk ?: 'public')->path($item->path);
            $entryName = $item->name;

            // Avoid two files sharing a display name from silently overwriting
            // one another inside the archive.
            if (isset($usedNames[$entryName])) {
                $usedNames[$entryName]++;
                $entryName = pathinfo($entryName, PATHINFO_FILENAME) . ' (' . $usedNames[$entryName] . ')'
                    . (pathinfo($entryName, PATHINFO_EXTENSION) ? '.' . pathinfo($entryName, PATHINFO_EXTENSION) : '');
            } else {
                $usedNames[$entryName] = 0;
            }

            $zip->addFile($fullPath, $entryName);
        }

        $zip->close();

        return $zipPath;
    }

    public function formatSize(?int $bytes): ?string
    {
        if ($bytes === null) {
            return null;
        }

        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $bytes;
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, $i > 0 && $size < 10 ? 1 : 0) . ' ' . $units[$i];
    }

    public function toArray(DmsItem $item): array
    {
        $meta = DmsItem::TYPES[$item->type] ?? DmsItem::TYPES['other'];
        $isFile = $item->kind === DmsItem::KIND_FILE;

        return [
            'id' => $item->id,
            'kind' => $item->kind,
            'type' => $item->type,
            'name' => $item->name,
            'icon' => $meta['icon'],
            'label' => $meta['label'],
            'size' => $item->size,
            'size_formatted' => $isFile ? $this->formatSize($item->size) : null,
            'date' => optional($item->updated_at)->toIso8601String(),
            'date_formatted' => optional($item->updated_at)->format('M j, Y'),
            'favorite' => (bool) $item->favorite,
            'children_count' => $item->is_folder ? (int) ($item->active_children_count ?? 0) : null,
            'url' => $isFile && $item->disk && $item->path ? Storage::disk($item->disk)->url($item->path) : null,
            'download_url' => $isFile ? route('admin.dms.download', $item) : null,
            'mime_type' => $item->mime_type,
            'extension' => $item->extension,
        ];
    }
}
