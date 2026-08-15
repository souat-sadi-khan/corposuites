@extends('admin.layout.app', ['title' => 'Document Management', 'modal' => 'lg'])

@section('content')

<div class="dms-page" id="dmsApp"
    data-data-url="{{ route('admin.dms.data') }}"
    data-folder-tree-url="{{ route('admin.dms.folder-tree') }}"
    data-folders-create-url="{{ route('admin.dms.folders.create') }}"
    data-folders-store-url="{{ route('admin.dms.folders.store') }}"
    data-upload-url="{{ route('admin.dms.upload') }}"
    data-bulk-trash-url="{{ route('admin.dms.bulk-trash') }}"
    data-bulk-restore-url="{{ route('admin.dms.bulk-restore') }}"
    data-bulk-delete-url="{{ route('admin.dms.bulk-delete') }}"
    data-bulk-move-url="{{ route('admin.dms.bulk-move') }}"
    data-bulk-download-url="{{ route('admin.dms.bulk-download') }}"
    data-empty-trash-url="{{ route('admin.dms.empty-trash') }}"
    data-rename-url-template="{{ route('admin.dms.rename', ['dms' => '__ID__']) }}"
    data-favorite-url-template="{{ route('admin.dms.favorite', ['dms' => '__ID__']) }}">

    <!-- Tabs -->
    <div class="dms-tabs-row">
        <div class="dms-tabs">
            <button class="dms-tab active" data-scope="all"><i class="ri-folders-line"></i> All Files <span class="dms-tab-count" id="dmsCountAll">0</span></button>
            <button class="dms-tab" data-scope="recent"><i class="ri-time-line"></i> Recent <span class="dms-tab-count" id="dmsCountRecent">0</span></button>
            <button class="dms-tab" data-scope="favorite"><i class="ri-star-line"></i> Favorites <span class="dms-tab-count" id="dmsCountFav">0</span></button>
            <button class="dms-tab" data-scope="trash"><i class="ri-delete-bin-line"></i> Trash <span class="dms-tab-count" id="dmsCountTrash">0</span></button>
        </div>
        <span class="dms-scope-count" id="dmsScopeCount">0 items</span>
    </div>

    <!-- Breadcrumb -->
    <div class="dms-breadcrumb" id="dmsBreadcrumb"></div>

    <!-- Toolbar -->
    <div class="dms-toolbar">
        <div class="dms-search-box">
            <i class="ri-search-line"></i>
            <input type="text" id="dmsSearchInput" placeholder="Search files and folders...">
        </div>

        <button type="button" id="openModal" class="dms-toolbar-btn dms-new-folder-btn" data-url="{{ route('admin.dms.folders.create') }}"><i class="ri-folder-add-line"></i> New Folder</button>

        <button class="dms-toolbar-btn" id="dmsUploadBtn"><i class="ri-upload-2-line"></i> Upload</button>
        <input type="file" id="dmsFileInput" multiple style="display:none;">

        <button class="dms-toolbar-btn" id="dmsUploadFolderBtn"><i class="ri-folder-upload-line"></i> Upload Folder</button>
        <input type="file" id="dmsFolderInput" webkitdirectory directory multiple style="display:none;">

        <button class="dms-toolbar-btn" id="dmsEmptyTrashBtn" style="display:none;"><i class="ri-delete-bin-7-line"></i> Empty Trash</button>

        <div class="dms-toolbar-spacer"></div>

        <div style="position:relative;">
            <button class="dms-toolbar-btn" id="dmsFilterBtn">
                <i class="ri-filter-3-line"></i> Filter <span class="dms-filter-dot"></span>
            </button>
            <div class="dms-dropdown" id="dmsFilterDropdown">
                <div class="dms-dropdown-title">File type</div>
                <label class="dms-dropdown-item"><input type="checkbox" value="image"> <i class="ri-image-2-line"></i> Image</label>
                <label class="dms-dropdown-item"><input type="checkbox" value="pdf"> <i class="ri-file-pdf-2-line"></i> PDF</label>
                <label class="dms-dropdown-item"><input type="checkbox" value="word"> <i class="ri-file-word-2-line"></i> Word</label>
                <label class="dms-dropdown-item"><input type="checkbox" value="excel"> <i class="ri-file-excel-2-line"></i> Excel</label>
                <label class="dms-dropdown-item"><input type="checkbox" value="video"> <i class="ri-video-line"></i> Video</label>
                <label class="dms-dropdown-item"><input type="checkbox" value="audio"> <i class="ri-music-2-line"></i> Audio</label>
                <label class="dms-dropdown-item"><input type="checkbox" value="zip"> <i class="ri-file-zip-line"></i> Archive</label>
                <label class="dms-dropdown-item"><input type="checkbox" value="svg"> <i class="ri-shape-2-line"></i> SVG</label>
                <label class="dms-dropdown-item"><input type="checkbox" value="other"> <i class="ri-file-line"></i> Other</label>
                <div class="dms-dropdown-divider"></div>
                <div class="dms-dropdown-item" id="dmsClearFilter"><i class="ri-close-circle-line"></i> Clear filters</div>
            </div>
        </div>

        <div style="position:relative;">
            <button class="dms-toolbar-btn" id="dmsSortBtn"><i class="ri-sort-desc"></i> Sort</button>
            <div class="dms-dropdown" id="dmsSortDropdown">
                <div class="dms-dropdown-title">Sort by</div>
                <div class="dms-dropdown-item selected" data-sort="name-asc"><i class="ri-sort-alphabet-asc"></i> Name (A&ndash;Z)</div>
                <div class="dms-dropdown-item" data-sort="name-desc"><i class="ri-sort-alphabet-desc"></i> Name (Z&ndash;A)</div>
                <div class="dms-dropdown-item" data-sort="date-desc"><i class="ri-calendar-line"></i> Newest first</div>
                <div class="dms-dropdown-item" data-sort="date-asc"><i class="ri-calendar-line"></i> Oldest first</div>
                <div class="dms-dropdown-item" data-sort="size-desc"><i class="ri-hard-drive-2-line"></i> Largest first</div>
                <div class="dms-dropdown-item" data-sort="size-asc"><i class="ri-hard-drive-2-line"></i> Smallest first</div>
            </div>
        </div>

        <div class="dms-view-toggle">
            <button id="dmsViewGridBtn" class="active" title="Grid view"><i class="ri-grid-fill"></i></button>
            <button id="dmsViewListBtn" title="List view"><i class="ri-list-check-2"></i></button>
            <button id="dmsViewTableBtn" title="Table view"><i class="ri-table-2"></i></button>
        </div>
    </div>

    <!-- Bulk action bar -->
    <div class="dms-bulkbar" id="dmsBulkbar">
        <span class="dms-bulkbar-count"><span id="dmsBulkCount">0</span> selected</span>
        <button class="dms-bulkbar-btn" id="dmsBulkMove"><i class="ri-folder-transfer-line"></i> Move</button>
        <button class="dms-bulkbar-btn" id="dmsBulkDownload"><i class="ri-download-2-line"></i> Download</button>
        <button class="dms-bulkbar-btn" id="dmsBulkRename"><i class="ri-edit-line"></i> Rename</button>
        <button class="dms-bulkbar-btn danger" id="dmsBulkDelete"><i class="ri-delete-bin-line"></i> Delete</button>
        <button class="dms-bulkbar-btn" id="dmsBulkRestore" style="display:none;"><i class="ri-arrow-go-back-line"></i> Restore</button>
        <button class="dms-bulkbar-btn danger" id="dmsBulkDeleteForever" style="display:none;"><i class="ri-delete-bin-7-line"></i> Delete Forever</button>
        <div class="dms-bulkbar-spacer"></div>
        <button class="dms-bulkbar-close" id="dmsBulkClose"><i class="ri-close-line"></i></button>
    </div>

    <!-- Body -->
    <div id="dmsBody">
        <div class="dms-grid" id="dmsGridView"></div>
        <div class="dms-list" id="dmsListView" style="display:none;"></div>
        <div class="dms-table-wrap" id="dmsTableViewWrap" style="display:none;">
            <table class="dms-table">
                <thead>
                    <tr>
                        <th style="width:34px;"><input type="checkbox" id="dmsTableSelectAll"></th>
                        <th data-sort="name">Name <i class="ri-arrow-up-down-line"></i></th>
                        <th data-sort="size" style="width:90px;">Size <i class="ri-arrow-up-down-line"></i></th>
                        <th data-sort="date" style="width:120px;">Modified <i class="ri-arrow-up-down-line"></i></th>
                        <th style="width:50px;"></th>
                        <th style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody id="dmsTableView"></tbody>
            </table>
        </div>
        <div class="dms-empty" id="dmsEmptyState" style="display:none;">
            <i class="ri-inbox-line"></i>
            <h3>No files found</h3>
            <p>Try adjusting your search or filters, or drop files anywhere to upload.</p>
        </div>
        <div class="dms-dropzone-overlay" id="dmsDropzoneOverlay">
            <div><i class="ri-upload-cloud-2-line"></i><span>Drop files to upload</span></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="dms-footer">
        <div class="dms-mode-switch">
            Infinite scroll
            <div class="dms-switch" id="dmsModeSwitch"></div>
        </div>
        <div class="dms-pagination" id="dmsPagination"></div>
    </div>
    <div class="dms-infinite-status" id="dmsInfiniteStatus">
        <div class="dms-spinner"></div> Loading more files&hellip;
    </div>

</div>

<!-- Preview modal -->
<div class="dms-overlay" id="dmsPreviewOverlay">
    <div class="dms-modal">
        <div class="dms-modal-head">
            <div class="dms-type-icon" id="dmsPvIcon"></div>
            <div style="min-width:0;">
                <div class="dms-modal-title" id="dmsPvName"></div>
                <div class="dms-modal-sub" id="dmsPvMeta"></div>
            </div>
            <a href="#" class="dms-modal-download" id="dmsPvDownload" title="Download"><i class="ri-download-2-line"></i></a>
            <button class="dms-modal-close" id="dmsPvClose"><i class="ri-close-line"></i></button>
        </div>
        <div class="dms-modal-preview" id="dmsPvPreview"></div>
        <div class="dms-modal-body">
            <div class="dms-modal-copy-title">Copy link</div>
            <div class="dms-copy-grid">
                <button class="dms-copy-btn" data-copy="url"><i class="ri-link"></i> Copy URL</button>
                <button class="dms-copy-btn" data-copy="path"><i class="ri-folder-line"></i> Copy Path</button>
                <button class="dms-copy-btn" data-copy="html"><i class="ri-code-line"></i> Copy HTML</button>
                <button class="dms-copy-btn" data-copy="markdown"><i class="ri-markdown-line"></i> Copy Markdown</button>
            </div>
        </div>
    </div>
</div>

<!-- Move modal — deliberately NOT using Bootstrap's ".modal-content" class here.
     The shared remote-modal system in main.js targets ".modal-content" with a
     page-wide, unscoped jQuery selector ($('.modal-content')) when loading the
     "New Folder" form, so a second ".modal-content" element anywhere else on
     the page (this one) would silently receive a duplicate copy of that form
     too — breaking Parsley's single-form assumption and the whole submit flow.
     ".dms-move-box" replicates the same visual styling without colliding. -->
<div class="modal fade" id="dmsMoveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="dms-move-box">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-folder-transfer-line me-2"></i>Move to folder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="dms-move-tree" id="dmsMoveTree"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-nx-primary" id="dmsMoveConfirm"><i class="ri-check-line"></i> Move Here</button>
            </div>
        </div>
    </div>
</div>

<!-- Right-click context menu -->
<div class="dms-context-menu" id="dmsContextMenu"></div>

<!-- Toast -->
<div class="dms-toast" id="dmsToast"><i class="ri-checkbox-circle-fill"></i> <span id="dmsToastMsg">Done</span></div>

@endsection

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════════
    DOCUMENT MANAGEMENT SYSTEM (DMS) — base admin panel feature
    Built on the app.css design tokens (--bg-*, --tx-*, --accent, --border …)
═══════════════════════════════════════════════════════════ */

.dms-page { max-width: 1280px; margin: 0 auto; padding: 4px 0 40px; }

/* ═══════════ TABS ═══════════ */
.dms-tabs-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid var(--border); margin-bottom: 16px; }
.dms-tabs { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.dms-tab { display: flex; align-items: center; gap: 7px; padding: 10px 14px; border: none; background: transparent; color: var(--tx-3); font-size: 13px; font-weight: 600; cursor: pointer; position: relative; transition: var(--ease); border-radius: 8px 8px 0 0; }
.dms-tab i { font-size: 15px; }
.dms-tab:hover { color: var(--tx-1); background: var(--bg-hover); }
.dms-tab.active { color: var(--accent); }
.dms-tab.active::after { content: ""; position: absolute; left: 8px; right: 8px; bottom: -1px; height: 2px; background: var(--accent); border-radius: 2px 2px 0 0; }
.dms-tab-count { font-size: 10.5px; font-weight: 700; color: var(--tx-3); background: var(--bg-hover); padding: 1px 7px; border-radius: 99px; }
.dms-tab.active .dms-tab-count { color: var(--accent); background: var(--accent-s); }
.dms-scope-count { font-size: 12px; color: var(--tx-3); white-space: nowrap; padding: 0 4px 8px 0; flex-shrink: 0; }

/* ═══════════ BREADCRUMB ═══════════ */
.dms-breadcrumb { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; font-size: 12.5px; color: var(--tx-3); margin-bottom: 14px; min-height: 20px; }
.dms-breadcrumb .crumb { cursor: pointer; padding: 3px 6px; border-radius: 6px; color: var(--tx-2); font-weight: 500; transition: var(--ease); display: flex; align-items: center; gap: 5px; }
.dms-breadcrumb .crumb:hover { background: var(--bg-hover); color: var(--tx-1); }
.dms-breadcrumb .crumb.current { color: var(--tx-1); font-weight: 700; cursor: default; }
.dms-breadcrumb .crumb.current:hover { background: none; }
.dms-breadcrumb .sep { color: var(--border-lt); font-size: 13px; }

/* ═══════════ TOOLBAR ═══════════ */
.dms-toolbar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
.dms-search-box { position: relative; flex: 1; min-width: 200px; max-width: 340px; }
.dms-search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--tx-3); font-size: 15px; }
.dms-search-box input { width: 100%; padding: 8px 12px 8px 34px; border-radius: 9px; border: 1px solid var(--border); background: var(--bg-surface); color: var(--tx-1); font-size: 12.5px; outline: none; transition: var(--ease); }
.dms-search-box input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-s); }
.dms-toolbar-spacer { flex: 1; }
.dms-toolbar-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 9px; border: 1px solid var(--border); background: var(--bg-surface); color: var(--tx-2); font-size: 12px; font-weight: 600; cursor: pointer; transition: var(--ease); position: relative; }
.dms-toolbar-btn:hover { background: var(--bg-hover); color: var(--tx-1); }
.dms-toolbar-btn i { font-size: 15px; }
.dms-toolbar-btn.active { border-color: var(--accent); color: var(--accent); background: var(--accent-s); }
.dms-filter-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--accent); display: none; }
.dms-toolbar-btn.active .dms-filter-dot { display: inline-block; }

.dms-view-toggle { display: flex; border: 1px solid var(--border); border-radius: 9px; overflow: hidden; background: var(--bg-surface); }
.dms-view-toggle button { display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border: none; border-left: 1px solid var(--border); background: transparent; color: var(--tx-3); cursor: pointer; font-size: 15px; transition: var(--ease); }
.dms-view-toggle button:first-child { border-left: none; }
.dms-view-toggle button:hover { background: var(--bg-hover); color: var(--tx-1); }
.dms-view-toggle button.active { background: var(--accent-s); color: var(--accent); }

.dms-dropdown { position: absolute; top: calc(100% + 6px); right: 0; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 11px; box-shadow: var(--shadow-sm); padding: 10px; min-width: 200px; z-index: 40; display: none; }
.dms-dropdown.open { display: block; }
.dms-dropdown-title { font-size: 10.5px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--tx-3); padding: 4px 8px 8px; }
.dms-dropdown-item { display: flex; align-items: center; gap: 9px; padding: 7px 8px; border-radius: 7px; font-size: 12.5px; color: var(--tx-1); cursor: pointer; transition: var(--ease); }
.dms-dropdown-item:hover { background: var(--bg-hover); }
.dms-dropdown-item input[type="checkbox"] { accent-color: var(--accent); }
.dms-dropdown-item i { font-size: 14px; color: var(--tx-3); }
.dms-dropdown-item.selected { color: var(--accent); font-weight: 600; }
.dms-dropdown-divider { height: 1px; background: var(--border-lt); margin: 6px 2px; }

.dms-bulkbar { display: none; align-items: center; gap: 8px; flex-wrap: wrap; background: var(--accent-s); border: 1px solid var(--accent-m); border-radius: 11px; padding: 10px 14px; margin-bottom: 14px; }
.dms-bulkbar.open { display: flex; }
.dms-bulkbar-count { font-size: 12.5px; font-weight: 700; color: var(--accent); margin-right: 4px; }
.dms-bulkbar-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 11px; border-radius: 8px; border: 1px solid transparent; background: var(--bg-surface); color: var(--tx-1); font-size: 12px; font-weight: 600; cursor: pointer; transition: var(--ease); }
.dms-bulkbar-btn:hover { background: var(--bg-hover); }
.dms-bulkbar-btn.danger { color: var(--red); }
.dms-bulkbar-btn.danger:hover { background: var(--red-s); }
.dms-bulkbar-spacer { flex: 1; }
.dms-bulkbar-close { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 7px; color: var(--tx-3); cursor: pointer; background: transparent; border: none; }
.dms-bulkbar-close:hover { background: var(--bg-hover); color: var(--tx-1); }

/* ═══════════ TYPE ICON COLORS ═══════════ */
.dms-type-icon { display: flex; align-items: center; justify-content: center; border-radius: 10px; flex-shrink: 0; }
.dms-type-folder { background: var(--accent-s); color: var(--accent); }
.dms-type-image  { background: var(--blue-s);   color: var(--blue); }
.dms-type-pdf    { background: var(--red-s);    color: var(--red); }
.dms-type-word   { background: var(--blue-s);   color: var(--blue); }
.dms-type-excel  { background: var(--green-s);  color: var(--green); }
.dms-type-video  { background: var(--amber-s);  color: var(--amber); }
.dms-type-audio  { background: var(--accent-s); color: var(--accent); }
.dms-type-zip    { background: var(--bg-hover); color: var(--tx-2); }
.dms-type-svg    { background: var(--accent-s); color: var(--accent); }
.dms-type-other  { background: var(--bg-hover); color: var(--tx-3); }

/* ═══════════ GRID VIEW ═══════════ */
.dms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; }
.dms-card { position: relative; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 12px; padding: 14px 12px 12px; cursor: pointer; transition: var(--ease); }
.dms-card:hover { border-color: var(--border-lt); box-shadow: var(--shadow-sm); }
.dms-card.selected { border-color: var(--accent); background: var(--accent-s); }
.dms-card-check { position: absolute; top: 10px; left: 10px; width: 17px; height: 17px; accent-color: var(--accent); opacity: 0; transition: opacity .12s; cursor: pointer; }
.dms-card:hover .dms-card-check, .dms-card.selected .dms-card-check { opacity: 1; }
.dms-card-fav { position: absolute; top: 8px; right: 8px; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; border-radius: 7px; color: var(--tx-3); background: transparent; border: none; cursor: pointer; font-size: 15px; transition: var(--ease); }
.dms-card-fav:hover { background: var(--bg-hover); }
.dms-card-fav.is-fav { color: var(--amber); }
.dms-card .dms-type-icon { width: 44px; height: 44px; font-size: 21px; margin: 6px auto 10px; }
.dms-card-name { font-size: 12px; font-weight: 600; color: var(--tx-1); text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dms-card-meta { font-size: 10.5px; color: var(--tx-3); text-align: center; margin-top: 3px; }

/* ═══════════ LIST VIEW ═══════════ */
.dms-list { display: flex; flex-direction: column; gap: 6px; }
.dms-list-row { display: flex; align-items: center; gap: 12px; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 10px; padding: 9px 12px; cursor: pointer; transition: var(--ease); }
.dms-list-row:hover { border-color: var(--border-lt); box-shadow: var(--shadow-xs); }
.dms-list-row.selected { border-color: var(--accent); background: var(--accent-s); }
.dms-list-row input[type="checkbox"] { accent-color: var(--accent); width: 16px; height: 16px; flex-shrink: 0; }
.dms-list-row .dms-type-icon { width: 32px; height: 32px; font-size: 16px; }
.dms-list-name { flex: 1; min-width: 0; font-size: 12.5px; font-weight: 600; color: var(--tx-1); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dms-list-meta { font-size: 11px; color: var(--tx-3); flex-shrink: 0; width: 90px; }
.dms-list-date { font-size: 11px; color: var(--tx-3); flex-shrink: 0; width: 110px; }
.dms-list-fav { flex-shrink: 0; background: none; border: none; color: var(--tx-3); cursor: pointer; font-size: 15px; }
.dms-list-fav.is-fav { color: var(--amber); }

/* ═══════════ TABLE VIEW ═══════════ */
.dms-table-wrap { background: var(--bg-surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.dms-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.dms-table th { text-align: left; font-size: 10.5px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: var(--tx-3); padding: 11px 14px; background: var(--bg-base); border-bottom: 1px solid var(--border); cursor: pointer; user-select: none; white-space: nowrap; }
.dms-table th i { font-size: 12px; margin-left: 3px; vertical-align: -1px; opacity: .5; }
.dms-table th.sorted { color: var(--accent); }
.dms-table th.sorted i { opacity: 1; }
.dms-table td { padding: 10px 14px; border-bottom: 1px solid var(--border-lt); color: var(--tx-1); vertical-align: middle; }
.dms-table tr:last-child td { border-bottom: none; }
.dms-table tr:hover td { background: var(--bg-hover); }
.dms-table tr.selected td { background: var(--accent-s); }
.dms-table input[type="checkbox"] { accent-color: var(--accent); width: 16px; height: 16px; }
.dms-table-name { display: flex; align-items: center; gap: 10px; font-weight: 600; cursor: pointer; }
.dms-table .dms-type-icon { width: 28px; height: 28px; font-size: 14px; }
.dms-table-meta { color: var(--tx-3); }
.dms-table-fav { background: none; border: none; color: var(--tx-3); cursor: pointer; font-size: 15px; }
.dms-table-fav.is-fav { color: var(--amber); }

.dms-menu-btn { background: none; border: none; color: var(--tx-3); cursor: pointer; font-size: 16px; padding: 4px; border-radius: 6px; }
.dms-menu-btn:hover { background: var(--bg-hover); color: var(--tx-1); }

/* ═══════════ EMPTY STATE ═══════════ */
.dms-empty { text-align: center; padding: 70px 20px; color: var(--tx-3); }
.dms-empty i { font-size: 34px; color: var(--border); margin-bottom: 10px; display: block; }
.dms-empty h3 { font-size: 14px; color: var(--tx-2); margin: 0 0 4px; }
.dms-empty p { font-size: 12px; margin: 0; }

/* ═══════════ DRAG & DROP UPLOAD OVERLAY ═══════════ */
#dmsBody { position: relative; transition: opacity .12s; }
#dmsBody.dms-loading { opacity: .45; pointer-events: none; }
.dms-dropzone-overlay { display: none; position: absolute; inset: -8px; background: var(--accent-s); border: 2px dashed var(--accent); border-radius: 14px; z-index: 20; align-items: center; justify-content: center; pointer-events: none; }
.dms-dropzone-overlay.active { display: flex; }
.dms-dropzone-overlay > div { display: flex; flex-direction: column; align-items: center; gap: 8px; color: var(--accent); font-weight: 700; font-size: 13px; }
.dms-dropzone-overlay i { font-size: 34px; }

/* ═══════════ FOOTER ═══════════ */
.dms-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 20px; flex-wrap: wrap; gap: 10px; }
.dms-mode-switch { display: flex; align-items: center; gap: 8px; font-size: 11.5px; color: var(--tx-3); }
.dms-switch { width: 34px; height: 19px; border-radius: 99px; background: var(--bg-hover); border: 1px solid var(--border); position: relative; cursor: pointer; transition: var(--ease); }
.dms-switch::after { content: ""; position: absolute; top: 1px; left: 1px; width: 15px; height: 15px; border-radius: 50%; background: var(--bg-surface); box-shadow: var(--shadow-xs); transition: var(--ease); }
.dms-switch.on { background: var(--accent); border-color: var(--accent); }
.dms-switch.on::after { transform: translateX(15px); background: #fff; }

.dms-pagination { display: flex; align-items: center; gap: 4px; }
.dms-page-btn { min-width: 30px; height: 30px; padding: 0 8px; display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-surface); color: var(--tx-2); font-size: 12px; font-weight: 600; cursor: pointer; transition: var(--ease); }
.dms-page-btn:hover { background: var(--bg-hover); }
.dms-page-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
.dms-page-btn:disabled { opacity: .4; cursor: not-allowed; }
.dms-page-btn:disabled:hover { background: var(--bg-surface); }

.dms-infinite-status { display: none; align-items: center; justify-content: center; gap: 8px; padding: 18px 0; font-size: 12px; color: var(--tx-3); width: 100%; }
.dms-spinner { width: 15px; height: 15px; border-radius: 50%; border: 2px solid var(--border); border-top-color: var(--accent); animation: dms-spin .7s linear infinite; }
@keyframes dms-spin { to { transform: rotate(360deg); } }

/* ═══════════ PREVIEW MODAL ═══════════ */
.dms-overlay { position: fixed; inset: 0; background: rgba(15, 17, 21, .5); display: none; align-items: center; justify-content: center; z-index: 1055; padding: 20px; }
.dms-overlay.open { display: flex; }
.dms-modal { background: var(--bg-surface); border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow-sm); width: 100%; max-width: 460px; overflow: hidden; }
.dms-modal-head { display: flex; align-items: center; gap: 10px; padding: 16px 16px 12px; border-bottom: 1px solid var(--border-lt); }
.dms-modal-head .dms-type-icon { width: 38px; height: 38px; font-size: 18px; }
.dms-modal-title { font-size: 13.5px; font-weight: 700; color: var(--tx-1); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dms-modal-sub { font-size: 11px; color: var(--tx-3); }
.dms-modal-download, .dms-modal-close { margin-left: auto; background: none; border: none; color: var(--tx-3); cursor: pointer; font-size: 18px; width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-decoration: none; }
.dms-modal-download:hover, .dms-modal-close:hover { background: var(--bg-hover); color: var(--tx-1); }
.dms-modal-preview { height: 200px; display: flex; align-items: center; justify-content: center; background: var(--bg-base); color: var(--tx-3); font-size: 44px; overflow: hidden; }
.dms-modal-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
.dms-modal-preview video { max-width: 100%; max-height: 100%; }
.dms-modal-preview audio { width: 90%; }
.dms-modal-body { padding: 14px 16px 16px; }
.dms-modal-copy-title { font-size: 10.5px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--tx-3); margin-bottom: 8px; }
.dms-copy-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.dms-copy-btn { display: flex; align-items: center; gap: 7px; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-base); color: var(--tx-1); font-size: 11.5px; font-weight: 600; cursor: pointer; transition: var(--ease); }
.dms-copy-btn:hover { background: var(--bg-hover); border-color: var(--border-lt); }
.dms-copy-btn i { font-size: 14px; color: var(--tx-3); }

/* ═══════════ MOVE MODAL ═══════════ */
/* Deliberately not ".modal-content" — see the HTML comment above the Move
   modal markup for why. Replicates the same box styling as .dms-modal. */
.dms-move-box { background: var(--bg-surface); border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow-sm); width: 100%; overflow: hidden; }
.dms-move-box .modal-header { border-bottom: 1px solid var(--border-lt); }
.dms-move-box .modal-title { font-size: 14px; font-weight: 700; color: var(--tx-1); display: flex; align-items: center; }
.dms-move-box .modal-footer { border-top: 1px solid var(--border-lt); }

/* ═══════════ MOVE MODAL TREE ═══════════ */
.dms-move-tree { max-height: 320px; overflow-y: auto; }
.dms-move-node { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px; cursor: pointer; font-size: 12.5px; color: var(--tx-1); font-weight: 600; transition: var(--ease); }
.dms-move-node:hover { background: var(--bg-hover); }
.dms-move-node.selected { background: var(--accent-s); color: var(--accent); }
.dms-move-node i { font-size: 15px; color: var(--accent); }
.dms-move-children { margin-left: 22px; }
.dms-move-empty { font-size: 12px; color: var(--tx-3); padding: 10px; text-align: center; }

/* ═══════════ RIGHT-CLICK CONTEXT MENU ═══════════ */
.dms-context-menu { position: fixed; z-index: 1060; display: none; min-width: 190px; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 11px; box-shadow: var(--shadow-sm); padding: 6px; }
.dms-context-menu.open { display: block; }
.dms-context-item { display: flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: 7px; font-size: 12.5px; font-weight: 500; color: var(--tx-1); cursor: pointer; transition: var(--ease); }
.dms-context-item:hover { background: var(--bg-hover); }
.dms-context-item i { font-size: 14px; color: var(--tx-3); width: 16px; text-align: center; }
.dms-context-item.danger { color: var(--red); }
.dms-context-item.danger:hover { background: var(--red-s); }
.dms-context-item.danger i { color: var(--red); }
.dms-context-divider { height: 1px; background: var(--border-lt); margin: 5px 2px; }
.dms-context-label { font-size: 10.5px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: var(--tx-3); padding: 6px 10px 4px; }

/* ═══════════ TOAST ═══════════ */
.dms-toast { position: fixed; bottom: 22px; left: 50%; transform: translateX(-50%) translateY(10px); background: var(--tx-1); color: var(--bg-surface); font-size: 12.5px; font-weight: 600; padding: 10px 16px; border-radius: 9px; box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 8px; opacity: 0; pointer-events: none; transition: opacity .18s, transform .18s; z-index: 2000; }
.dms-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.dms-toast i { color: var(--green); }

/* ═══════════ RESPONSIVE ═══════════ */
@media (max-width: 680px) {
    .dms-tabs { overflow-x: auto; }
    .dms-list-meta, .dms-list-date { display: none; }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/system/js/pages/dms.js') }}"></script>
@endpush
