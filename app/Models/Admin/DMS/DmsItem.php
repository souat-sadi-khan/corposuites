<?php

namespace App\Models\Admin\DMS;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DmsItem extends Model
{
    protected $table = 'dms_items';

    public const KIND_FOLDER = 'folder';
    public const KIND_FILE = 'file';

    /**
     * Type => [icon, label] used by both the JSON API (icon class) and the
     * Filter dropdown. Kept as the single source of truth so the backend and
     * the dms.js frontend can never disagree on what a type looks like.
     */
    public const TYPES = [
        'folder' => ['icon' => 'ri-folder-3-fill', 'label' => 'Folder'],
        'image'  => ['icon' => 'ri-image-2-line', 'label' => 'Image'],
        'pdf'    => ['icon' => 'ri-file-pdf-2-line', 'label' => 'PDF'],
        'word'   => ['icon' => 'ri-file-word-2-line', 'label' => 'Word'],
        'excel'  => ['icon' => 'ri-file-excel-2-line', 'label' => 'Excel'],
        'video'  => ['icon' => 'ri-video-line', 'label' => 'Video'],
        'audio'  => ['icon' => 'ri-music-2-line', 'label' => 'Audio'],
        'zip'    => ['icon' => 'ri-file-zip-line', 'label' => 'Archive'],
        'svg'    => ['icon' => 'ri-shape-2-line', 'label' => 'SVG'],
        'other'  => ['icon' => 'ri-file-line', 'label' => 'File'],
    ];

    /**
     * Extension => type. Anything not listed here falls back to 'other'.
     */
    public const EXTENSION_MAP = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico'],
        'pdf'   => ['pdf'],
        'word'  => ['doc', 'docx', 'rtf', 'odt'],
        'excel' => ['xls', 'xlsx', 'csv', 'ods'],
        'video' => ['mp4', 'mov', 'avi', 'mkv', 'webm', 'm4v'],
        'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'aac'],
        'zip'   => ['zip', 'rar', '7z', 'tar', 'gz'],
        'svg'   => ['svg'],
    ];

    /** Window used for the "Recent" scope/tab count. */
    public const RECENT_DAYS = 30;

    protected $fillable = [
        'kind',
        'type',
        'name',
        'parent_id',
        'disk',
        'path',
        'extension',
        'mime_type',
        'size',
        'favorite',
        'trashed',
        'trashed_at',
        'created_by',
    ];

    protected $casts = [
        'favorite' => 'boolean',
        'trashed' => 'boolean',
        'trashed_at' => 'datetime',
        'size' => 'integer',
    ];

    public static function typeFromExtension(?string $extension): string
    {
        $extension = strtolower((string) $extension);

        foreach (self::EXTENSION_MAP as $type => $extensions) {
            if (in_array($extension, $extensions, true)) {
                return $type;
            }
        }

        return 'other';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Non-trashed children only — used for the folder item-count column
     * without pulling in items sitting in the trash.
     */
    public function activeChildren(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('trashed', false);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function scopeNotTrashed($query)
    {
        return $query->where('trashed', false);
    }

    public function scopeFolders($query)
    {
        return $query->where('kind', self::KIND_FOLDER);
    }

    public function scopeFiles($query)
    {
        return $query->where('kind', self::KIND_FILE);
    }

    public function getIsFolderAttribute(): bool
    {
        return $this->kind === self::KIND_FOLDER;
    }
}
