/* ═══════════════════════════════════════════════════════════
    DOCUMENT MANAGEMENT SYSTEM (DMS) — base admin panel feature
    Server-driven file manager: every list/mutation call hits
    the Laravel endpoints declared as data-* attributes on
    #dmsApp; nothing here holds the real dataset client-side.
═══════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    const $app = $('#dmsApp');
    if (!$app.length) return;

    const routes = {
        data: $app.data('data-url'),
        folderTree: $app.data('folder-tree-url'),
        foldersCreate: $app.data('folders-create-url'),
        foldersStore: $app.data('folders-store-url'),
        upload: $app.data('upload-url'),
        bulkTrash: $app.data('bulk-trash-url'),
        bulkRestore: $app.data('bulk-restore-url'),
        bulkDelete: $app.data('bulk-delete-url'),
        bulkMove: $app.data('bulk-move-url'),
        bulkDownload: $app.data('bulk-download-url'),
        emptyTrash: $app.data('empty-trash-url'),
        renameTemplate: $app.data('rename-url-template'),
        favoriteTemplate: $app.data('favorite-url-template'),
    };

    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

    function renameUrl(id) { return routes.renameTemplate.replace('__ID__', id); }
    function favoriteUrl(id) { return routes.favoriteTemplate.replace('__ID__', id); }

    // ── State (view + sort are remembered across page loads) ──
    const state = {
        scope: 'all',
        currentFolder: null,
        view: localStorage.getItem('dms_view') || 'grid',
        query: '',
        typeFilter: new Set(),
        sort: localStorage.getItem('dms_sort') || 'name-asc',
        selected: new Set(),
        page: 1,
        pageSize: 24,
        infiniteScroll: false,
        infiniteItems: [],
        items: [],
        total: 0,
        breadcrumb: [],
        counts: { all: 0, recent: 0, favorite: 0, trash: 0 },
        loading: false,
    };

    let moveSelectedFolderId; // undefined = nothing chosen yet in the Move modal
    let moveTargetIds = [];
    let searchDebounce = null;
    let contextMenuIds = [];
    let contextMenuItem = null;

    // ── Helpers ──
    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, ch => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[ch]));
    }

    function currentDisplayItems() {
        return state.infiniteScroll ? state.infiniteItems : state.items;
    }

    function findLoadedItem(id) {
        return currentDisplayItems().find(i => i.id === id);
    }

    function toast(msg) {
        const $t = $('#dmsToast');
        $('#dmsToastMsg').text(msg);
        $t.addClass('show');
        clearTimeout(toast._t);
        toast._t = setTimeout(() => $t.removeClass('show'), 2200);
    }

    function ajaxErrorMessage(xhr, fallback) {
        return (xhr && xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || fallback;
    }

    // ── Fetch list from the server ──
    function fetchData(opts) {
        opts = opts || {};
        if (state.loading) return;
        state.loading = true;
        if (!opts.append) $('#dmsBody').addClass('dms-loading');

        const params = {
            scope: state.scope,
            q: state.query,
            sort: state.sort,
            page: state.page,
            per_page: state.pageSize,
        };
        if (state.scope === 'all') params.folder_id = state.currentFolder || '';
        if (state.typeFilter.size) params.types = [...state.typeFilter];

        $.get(routes.data, params)
            .done(function (res) {
                state.total = res.total;
                state.counts = res.counts;
                state.breadcrumb = res.breadcrumb;

                if (state.infiniteScroll) {
                    state.infiniteItems = opts.append ? state.infiniteItems.concat(res.items) : res.items;
                } else {
                    state.items = res.items;
                }
                render();
            })
            .fail(function () {
                toast('Failed to load files');
            })
            .always(function () {
                state.loading = false;
                $('#dmsBody').removeClass('dms-loading');
            });
    }

    // ── Render ──
    function render() {
        const items = currentDisplayItems();
        renderGrid(items);
        renderList(items);
        renderTable(items);
        renderPagination();
        renderCounts();
        renderBreadcrumb();
        syncNewFolderButton();

        $('#dmsEmptyState').toggle(items.length === 0);
        $('#dmsScopeCount').text(state.total + ' item' + (state.total === 1 ? '' : 's'));
        $('#dmsInfiniteStatus').toggle(state.infiniteScroll && items.length < state.total);
    }

    function itemMeta(item) {
        if (item.kind === 'folder') {
            const n = item.children_count || 0;
            return n + ' item' + (n === 1 ? '' : 's');
        }
        return escapeHtml(item.label) + ' · ' + (item.size_formatted || '0 B');
    }

    function renderGrid(items) {
        $('#dmsGridView').html(items.map(f => `
            <div class="dms-card ${state.selected.has(f.id) ? 'selected' : ''}" data-id="${f.id}" data-kind="${f.kind}">
                <input type="checkbox" class="dms-card-check" data-select="${f.id}" ${state.selected.has(f.id) ? 'checked' : ''}>
                <button class="dms-card-fav ${f.favorite ? 'is-fav' : ''}" data-fav="${f.id}">
                    <i class="${f.favorite ? 'ri-star-fill' : 'ri-star-line'}"></i>
                </button>
                <div class="dms-type-icon dms-type-${f.type}"><i class="${f.icon}"></i></div>
                <div class="dms-card-name" title="${escapeHtml(f.name)}">${escapeHtml(f.name)}</div>
                <div class="dms-card-meta">${itemMeta(f)}</div>
            </div>
        `).join(''));
    }

    function renderList(items) {
        $('#dmsListView').html(items.map(f => `
            <div class="dms-list-row ${state.selected.has(f.id) ? 'selected' : ''}" data-id="${f.id}" data-kind="${f.kind}">
                <input type="checkbox" data-select="${f.id}" ${state.selected.has(f.id) ? 'checked' : ''}>
                <div class="dms-type-icon dms-type-${f.type}"><i class="${f.icon}"></i></div>
                <div class="dms-list-name" title="${escapeHtml(f.name)}">${escapeHtml(f.name)}</div>
                <div class="dms-list-meta">${f.kind === 'folder' ? (f.children_count || 0) + ' items' : (f.size_formatted || '0 B')}</div>
                <div class="dms-list-date">${escapeHtml(f.date_formatted || '')}</div>
                <button class="dms-list-fav ${f.favorite ? 'is-fav' : ''}" data-fav="${f.id}">
                    <i class="${f.favorite ? 'ri-star-fill' : 'ri-star-line'}"></i>
                </button>
                <button class="dms-menu-btn" data-preview="${f.id}"><i class="ri-more-2-fill"></i></button>
            </div>
        `).join(''));
    }

    function renderTable(items) {
        $('#dmsTableView').html(items.map(f => `
            <tr class="${state.selected.has(f.id) ? 'selected' : ''}" data-id="${f.id}" data-kind="${f.kind}">
                <td><input type="checkbox" data-select="${f.id}" ${state.selected.has(f.id) ? 'checked' : ''}></td>
                <td>
                    <div class="dms-table-name" data-id="${f.id}" data-kind="${f.kind}">
                        <div class="dms-type-icon dms-type-${f.type}"><i class="${f.icon}"></i></div>
                        ${escapeHtml(f.name)}
                    </div>
                </td>
                <td class="dms-table-meta">${f.kind === 'folder' ? (f.children_count || 0) + ' items' : (f.size_formatted || '0 B')}</td>
                <td class="dms-table-meta">${escapeHtml(f.date_formatted || '')}</td>
                <td><button class="dms-table-fav ${f.favorite ? 'is-fav' : ''}" data-fav="${f.id}"><i class="${f.favorite ? 'ri-star-fill' : 'ri-star-line'}"></i></button></td>
                <td><button class="dms-menu-btn" data-preview="${f.id}"><i class="ri-more-2-fill"></i></button></td>
            </tr>
        `).join(''));
    }

    function paginationRange(current, total) {
        const delta = 2;
        const range = [];
        for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) range.push(i);
        }
        const withDots = [];
        let last = null;
        range.forEach(i => {
            if (last !== null) {
                if (i - last === 2) withDots.push(last + 1);
                else if (i - last !== 1) withDots.push('...');
            }
            withDots.push(i);
            last = i;
        });
        return withDots;
    }

    function renderPagination() {
        const $wrap = $('#dmsPagination');
        if (state.infiniteScroll) { $wrap.html(''); return; }

        const maxPage = Math.max(1, Math.ceil(state.total / state.pageSize));
        if (state.page > maxPage) state.page = maxPage;

        let html = `<button class="dms-page-btn" id="dmsPrevPage" ${state.page === 1 ? 'disabled' : ''}><i class="ri-arrow-left-s-line"></i></button>`;
        paginationRange(state.page, maxPage).forEach(p => {
            html += p === '...'
                ? `<span class="dms-page-btn" style="cursor:default;border-color:transparent;background:transparent;">&hellip;</span>`
                : `<button class="dms-page-btn ${p === state.page ? 'active' : ''}" data-page="${p}">${p}</button>`;
        });
        html += `<button class="dms-page-btn" id="dmsNextPage" ${state.page === maxPage ? 'disabled' : ''}><i class="ri-arrow-right-s-line"></i></button>`;
        $wrap.html(html);
    }

    function renderCounts() {
        $('#dmsCountAll').text(state.counts.all || 0);
        $('#dmsCountRecent').text(state.counts.recent || 0);
        $('#dmsCountFav').text(state.counts.favorite || 0);
        $('#dmsCountTrash').text(state.counts.trash || 0);
    }

    function renderBreadcrumb() {
        const $el = $('#dmsBreadcrumb');
        if (state.scope !== 'all') { $el.html(''); return; }

        let html = `<span class="crumb ${state.breadcrumb.length === 0 ? 'current' : ''}" data-nav="root"><i class="ri-home-4-line"></i> Home</span>`;
        state.breadcrumb.forEach((f, i) => {
            html += `<span class="sep">/</span><span class="crumb ${i === state.breadcrumb.length - 1 ? 'current' : ''}" data-nav="${f.id}">${escapeHtml(f.name)}</span>`;
        });
        $el.html(html);
    }

    function renderBulkbar() {
        const n = state.selected.size;
        $('#dmsBulkbar').toggleClass('open', n > 0);
        $('#dmsBulkCount').text(n);

        const inTrash = state.scope === 'trash';
        $('#dmsBulkDelete').toggle(!inTrash);
        $('#dmsBulkMove').toggle(!inTrash);
        $('#dmsBulkRename').toggle(!inTrash && n === 1);
        $('#dmsBulkDownload').toggle(true);
        $('#dmsBulkRestore').toggle(inTrash);
        $('#dmsBulkDeleteForever').toggle(inTrash);
    }

    // Keeps the "New Folder" remote-modal button's target URL in sync with
    // whatever folder is currently open, and hides it while browsing Trash.
    function syncNewFolderButton() {
        const $btn = $('.dms-new-folder-btn');
        $btn.toggle(state.scope !== 'trash');

        const parentId = state.scope === 'all' && state.currentFolder ? state.currentFolder : '';
        const url = routes.foldersCreate + (parentId ? ('?parent_id=' + parentId) : '');

        // Set both the raw DOM attribute AND jQuery's own internal .data()
        // cache. main.js's shared #openModal handler reads the URL via
        // $(this).data('url') — jQuery lazily caches a data-* attribute the
        // FIRST time .data() is called on an element and never re-reads the
        // DOM afterward, so a plain .attr() update alone is invisible to it
        // (the classic jQuery .data()/.attr() desync gotcha). Without this,
        // whichever folder the button was first clicked in during a given
        // page load stays "stuck" for every click after that.
        $btn.attr('data-url', url).data('url', url);
    }

    function toggleScopeToolbar(scope) {
        $('#dmsUploadBtn').toggle(scope !== 'trash');
        $('#dmsUploadFolderBtn').toggle(scope !== 'trash');
        $('#dmsEmptyTrashBtn').toggle(scope === 'trash');
        syncNewFolderButton();
    }

    // ── View switching (persisted) ──
    function setView(view) {
        state.view = view;
        localStorage.setItem('dms_view', view);
        $('#dmsGridView').css('display', view === 'grid' ? 'grid' : 'none');
        $('#dmsListView').css('display', view === 'list' ? 'flex' : 'none');
        $('#dmsTableViewWrap').css('display', view === 'table' ? 'block' : 'none');
        $('.dms-view-toggle button').removeClass('active');
        $('#dmsView' + view[0].toUpperCase() + view.slice(1) + 'Btn').addClass('active');
    }
    $('#dmsViewGridBtn').on('click', () => setView('grid'));
    $('#dmsViewListBtn').on('click', () => setView('list'));
    $('#dmsViewTableBtn').on('click', () => setView('table'));

    // ── Tabs / scope ──
    function switchScope(scope) {
        state.scope = scope;
        state.page = 1;
        state.infiniteItems = [];
        state.selected.clear();
        $('.dms-tab').removeClass('active');
        $('.dms-tab[data-scope="' + scope + '"]').addClass('active');
        toggleScopeToolbar(scope);
        renderBulkbar();
        fetchData();
    }
    $('.dms-tab').on('click', function () { switchScope($(this).data('scope')); });

    // ── Folder navigation ──
    function openFolder(id) {
        if (state.scope !== 'all') {
            state.scope = 'all';
            $('.dms-tab').removeClass('active');
            $('.dms-tab[data-scope="all"]').addClass('active');
            toggleScopeToolbar('all');
        }
        state.currentFolder = id;
        state.page = 1;
        state.infiniteItems = [];
        state.selected.clear();
        renderBulkbar();
        fetchData();
    }

    $(document).on('click', '.crumb', function () {
        if ($(this).hasClass('current')) return;
        state.currentFolder = $(this).data('nav') === 'root' ? null : Number($(this).data('nav'));
        state.page = 1;
        fetchData();
    });

    // The shared #openModal + .ajax-form system (main.js) expects a global
    // `dataTableInstance.ajax.reload()` to refresh the list after a
    // successful submit — the same fake-object trick this project's own
    // non-DataTable pages (e.g. the Categories tree) already use, so no
    // changes were needed in main.js itself.
    window.dataTableInstance = {
        ajax: {
            reload: function () {
                if (state.scope !== 'all') switchScope('all'); else fetchData();
            },
        },
    };

    // ── Upload (button + drag & drop) ──
    function uploadFiles(fileList) {
        if (!fileList || !fileList.length) return;

        const formData = new FormData();
        formData.append('parent_id', state.scope === 'all' && state.currentFolder ? state.currentFolder : '');
        [...fileList].forEach(file => formData.append('files[]', file));

        toast('Uploading ' + fileList.length + ' file(s)…');

        $.ajax({
            url: routes.upload,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        }).done(function (res) {
            toast(res.message);
            if (state.scope !== 'all') switchScope('all'); else fetchData();
        }).fail(function (xhr) {
            toast(ajaxErrorMessage(xhr, 'Upload failed'));
        });
    }

    $('#dmsUploadBtn').on('click', () => $('#dmsFileInput').trigger('click'));
    $('#dmsFileInput').on('change', function () {
        uploadFiles(this.files);
        this.value = '';
    });

    // ── Upload a whole folder, preserving its subfolder structure ──
    // Uses the browser's native folder picker (input[webkitdirectory]) — each
    // selected File carries a webkitRelativePath like "MyFolder/sub/file.png"
    // that's used to recreate the same folder tree here via the existing
    // create-folder/upload endpoints, one request at a time (sequential, not
    // parallel, so two files that need the same new subfolder can never race
    // and create it twice). Falls back to a flat upload for any file with no
    // relative path (older browsers without folder-picker support).
    async function uploadFolderStructure(fileList) {
        if (!fileList || !fileList.length) return;

        const baseParentId = state.scope === 'all' && state.currentFolder ? state.currentFolder : null;
        const folderIdByPath = new Map();
        let uploaded = 0;
        const total = fileList.length;

        async function ensureFolderPath(segments) {
            let path = '';
            let parentId = baseParentId;

            for (const segment of segments) {
                path = path ? path + '/' + segment : segment;

                if (folderIdByPath.has(path)) {
                    parentId = folderIdByPath.get(path);
                    continue;
                }

                const res = await $.post(routes.foldersStore, { name: segment, parent_id: parentId });
                parentId = res.item.id;
                folderIdByPath.set(path, parentId);
            }

            return parentId;
        }

        toast('Uploading folder (0/' + total + ')…');

        try {
            for (const file of fileList) {
                const relativePath = file.webkitRelativePath || '';
                const segments = relativePath ? relativePath.split('/').slice(0, -1) : [];
                const parentId = await ensureFolderPath(segments);

                const formData = new FormData();
                formData.append('parent_id', parentId || '');
                formData.append('files[]', file);

                await $.ajax({ url: routes.upload, method: 'POST', data: formData, processData: false, contentType: false });
                uploaded++;
                toast('Uploading folder (' + uploaded + '/' + total + ')…');
            }

            toast('Folder uploaded — ' + uploaded + ' file(s)');
        } catch (xhr) {
            toast(ajaxErrorMessage(xhr, 'Folder upload failed after ' + uploaded + '/' + total + ' file(s)'));
        } finally {
            if (state.scope !== 'all') switchScope('all'); else fetchData();
        }
    }

    $('#dmsUploadFolderBtn').on('click', () => $('#dmsFolderInput').trigger('click'));
    $('#dmsFolderInput').on('change', function () {
        // this.files is a LIVE FileList tied to the input element, not a
        // snapshot. uploadFolderStructure() is async and pauses at each
        // `await`; resetting this.value right after starting it would clear
        // that live list out from under the still-running loop, silently
        // truncating it to whatever file was being processed at the time.
        // Copying to a plain array first freezes the exact set of files.
        const files = [...this.files];
        this.value = '';
        uploadFolderStructure(files);
    });

    let dragCounter = 0;
    const $dmsBody = $('#dmsBody');
    $dmsBody.on('dragenter', function (e) {
        e.preventDefault();
        if (state.scope === 'trash') return;
        dragCounter++;
        $('#dmsDropzoneOverlay').addClass('active');
    });
    $dmsBody.on('dragover', e => e.preventDefault());
    $dmsBody.on('dragleave', function (e) {
        e.preventDefault();
        dragCounter = Math.max(0, dragCounter - 1);
        if (dragCounter === 0) $('#dmsDropzoneOverlay').removeClass('active');
    });
    $dmsBody.on('drop', function (e) {
        e.preventDefault();
        dragCounter = 0;
        $('#dmsDropzoneOverlay').removeClass('active');
        if (state.scope === 'trash') { toast('Switch to All Files to upload'); return; }
        const files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
        uploadFiles(files);
    });

    // ── Search ──
    $('#dmsSearchInput').on('input', function () {
        const val = $(this).val();
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(function () {
            state.query = val;
            state.page = 1;
            state.infiniteItems = [];
            fetchData();
        }, 300);
    });

    // ── Filter dropdown ──
    $('#dmsFilterBtn').on('click', function (e) {
        e.stopPropagation();
        $('#dmsFilterDropdown').toggleClass('open');
        $('#dmsSortDropdown').removeClass('open');
    });
    $('#dmsFilterDropdown input[type="checkbox"]').on('change', function () {
        if (this.checked) state.typeFilter.add(this.value); else state.typeFilter.delete(this.value);
        $('#dmsFilterBtn').toggleClass('active', state.typeFilter.size > 0);
        state.page = 1;
        fetchData();
    });
    $('#dmsClearFilter').on('click', function () {
        state.typeFilter.clear();
        $('#dmsFilterDropdown input[type="checkbox"]').prop('checked', false);
        $('#dmsFilterBtn').removeClass('active');
        state.page = 1;
        fetchData();
    });

    // ── Sort dropdown (persisted) ──
    $('#dmsSortBtn').on('click', function (e) {
        e.stopPropagation();
        $('#dmsSortDropdown').toggleClass('open');
        $('#dmsFilterDropdown').removeClass('open');
    });
    function applySort(sort) {
        state.sort = sort;
        localStorage.setItem('dms_sort', sort);
        $('#dmsSortDropdown [data-sort]').removeClass('selected');
        $('#dmsSortDropdown [data-sort="' + sort + '"]').addClass('selected');
        $('.dms-table th').removeClass('sorted');
        $('.dms-table th[data-sort="' + sort.split('-')[0] + '"]').addClass('sorted');
    }
    $('#dmsSortDropdown [data-sort]').on('click', function () {
        applySort($(this).data('sort'));
        $('#dmsSortDropdown').removeClass('open');
        fetchData();
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#dmsFilterBtn, #dmsFilterDropdown').length) $('#dmsFilterDropdown').removeClass('open');
        if (!$(e.target).closest('#dmsSortBtn, #dmsSortDropdown').length) $('#dmsSortDropdown').removeClass('open');
    });

    // ── Table header sort ──
    $(document).on('click', '.dms-table th[data-sort]', function () {
        const key = $(this).data('sort');
        const [curKey, curDir] = state.sort.split('-');
        applySort(key + '-' + (curKey === key && curDir === 'desc' ? 'asc' : 'desc'));
        fetchData();
    });

    // ── Selection ──
    $(document).on('change', '[data-select]', function () {
        const id = Number($(this).data('select'));
        if (this.checked) state.selected.add(id); else state.selected.delete(id);
        updateSelectionUi();
        renderBulkbar();
    });
    $(document).on('change', '#dmsTableSelectAll', function () {
        const ids = currentDisplayItems().map(f => f.id);
        if (this.checked) ids.forEach(id => state.selected.add(id));
        else ids.forEach(id => state.selected.delete(id));
        render();
        renderBulkbar();
    });

    function updateSelectionUi() {
        $('.dms-card, .dms-list-row, .dms-table tr').each(function () {
            const id = Number($(this).data('id'));
            const selected = state.selected.has(id);
            $(this).toggleClass('selected', selected);
            $(this).find('[data-select]').prop('checked', selected);
        });
    }

    // ── Favorite toggle / open folder / open preview ──
    function doToggleFavorite(id) {
        $.post(favoriteUrl(id)).done(function (res) {
            toast(res.message);
            fetchData();
        });
    }

    $(document).on('click', '[data-fav]', function (e) {
        e.stopPropagation();
        doToggleFavorite(Number($(this).data('fav')));
    });

    $(document).on('click', '.dms-table-name', function () {
        const id = Number($(this).data('id'));
        $(this).data('kind') === 'folder' ? openFolder(id) : openPreview(id);
    });

    $(document).on('click', '[data-preview]', function () {
        const item = findLoadedItem(Number($(this).data('preview')));
        if (!item) return;
        item.kind === 'folder' ? openFolder(item.id) : openPreview(item.id);
    });

    $(document).on('click', '.dms-card, .dms-list-row', function (e) {
        if ($(e.target).closest('input, [data-fav], .dms-menu-btn').length) return;
        const id = Number($(this).data('id'));
        $(this).data('kind') === 'folder' ? openFolder(id) : openPreview(id);
    });

    // ── Pagination ──
    $(document).on('click', '#dmsPrevPage', () => { state.page--; fetchData(); });
    $(document).on('click', '#dmsNextPage', () => { state.page++; fetchData(); });
    $(document).on('click', '[data-page]', function () { state.page = Number($(this).data('page')); fetchData(); });

    // ── Infinite scroll ──
    $('#dmsModeSwitch').on('click', function () {
        state.infiniteScroll = !state.infiniteScroll;
        $(this).toggleClass('on', state.infiniteScroll);
        state.page = 1;
        state.infiniteItems = [];
        fetchData();
    });
    $(window).on('scroll', function () {
        if (!state.infiniteScroll || state.loading) return;
        if (state.infiniteItems.length >= state.total) return;
        const nearBottom = window.innerHeight + window.scrollY >= document.body.offsetHeight - 200;
        if (nearBottom) {
            state.page++;
            fetchData({ append: true });
        }
    });

    // ── Reusable bulk/context actions (shared by the bulk bar and the
    // right-click context menu, so the two paths can never drift apart) ──
    function doTrash(ids) {
        $.post(routes.bulkTrash, { ids }).done(function (res) {
            toast(res.message);
            state.selected.clear();
            renderBulkbar();
            fetchData();
        });
    }

    function doRestore(ids) {
        $.post(routes.bulkRestore, { ids }).done(function (res) {
            toast(res.message);
            state.selected.clear();
            renderBulkbar();
            fetchData();
        });
    }

    function doDeleteForever(ids) {
        if (!confirm('Permanently delete ' + ids.length + ' item(s)? This cannot be undone.')) return;
        $.ajax({ url: routes.bulkDelete, method: 'POST', data: { ids } }).done(function (res) {
            toast(res.message);
            state.selected.clear();
            renderBulkbar();
            fetchData();
        });
    }

    function doRename(id) {
        const item = findLoadedItem(id);
        const name = prompt('Rename', item ? item.name : '');
        if (!name || !name.trim()) return;
        $.post(renameUrl(id), { name: name.trim() }).done(function (res) {
            toast(res.message);
            fetchData();
        }).fail(function (xhr) {
            toast(ajaxErrorMessage(xhr, 'Could not rename'));
        });
    }

    function doDownload(ids) {
        if (!ids.length) return;

        if (ids.length === 1) {
            const item = findLoadedItem(ids[0]);
            if (item && item.kind === 'file') {
                window.location = item.download_url;
                return;
            }
        }

        const $form = $('<form>', { method: 'POST', action: routes.bulkDownload, style: 'display:none;' });
        $form.append($('<input>', { type: 'hidden', name: '_token', value: csrfToken }));
        ids.forEach(id => $form.append($('<input>', { type: 'hidden', name: 'ids[]', value: id })));
        $('body').append($form);
        $form.trigger('submit');
        setTimeout(() => $form.remove(), 1000);
    }

    $('#dmsBulkClose').on('click', function () { state.selected.clear(); renderBulkbar(); updateSelectionUi(); });
    $('#dmsBulkDelete').on('click', () => doTrash([...state.selected]));
    $('#dmsBulkRestore').on('click', () => doRestore([...state.selected]));
    $('#dmsBulkDeleteForever').on('click', () => doDeleteForever([...state.selected]));
    $('#dmsBulkRename').on('click', () => doRename([...state.selected][0]));
    $('#dmsBulkDownload').on('click', () => doDownload([...state.selected]));

    $('#dmsEmptyTrashBtn').on('click', function () {
        if (!confirm('Permanently delete everything in the trash? This cannot be undone.')) return;
        $.post(routes.emptyTrash).done(function (res) {
            toast(res.message);
            fetchData();
        });
    });

    // ── Move modal ──
    function buildMoveNodes(nodes) {
        return nodes.map(n => `
            <div class="dms-move-node" data-move-id="${n.id}"><i class="ri-folder-3-fill"></i> ${escapeHtml(n.name)}</div>
            ${n.children.length ? '<div class="dms-move-children">' + buildMoveNodes(n.children) + '</div>' : ''}
        `).join('');
    }

    function renderMoveTree(tree) {
        let html = `<div class="dms-move-node" data-move-id=""><i class="ri-home-4-line"></i> Root (My Files)</div>`;
        html += buildMoveNodes(tree);
        $('#dmsMoveTree').html(html);
        $('#dmsMoveTree .dms-move-node').removeClass('selected');
    }

    function openMoveModalFor(ids) {
        moveTargetIds = ids;
        moveSelectedFolderId = undefined;
        $.get(routes.folderTree, { exclude: ids }).done(function (res) {
            renderMoveTree(res.tree);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('dmsMoveModal')).show();
        });
    }

    $('#dmsBulkMove').on('click', () => openMoveModalFor([...state.selected]));

    $(document).on('click', '#dmsMoveTree .dms-move-node', function () {
        $('#dmsMoveTree .dms-move-node').removeClass('selected');
        $(this).addClass('selected');
        const raw = $(this).attr('data-move-id');
        moveSelectedFolderId = raw === '' ? null : Number(raw);
    });

    $('#dmsMoveConfirm').on('click', function () {
        if (moveSelectedFolderId === undefined) { toast('Choose a destination folder first'); return; }

        $.post(routes.bulkMove, { ids: moveTargetIds, destination_id: moveSelectedFolderId }).done(function (res) {
            bootstrap.Modal.getInstance(document.getElementById('dmsMoveModal'))?.hide();
            toast(res.message);
            state.selected.clear();
            renderBulkbar();
            fetchData();
        }).fail(function (xhr) {
            toast(ajaxErrorMessage(xhr, 'Could not move item(s)'));
        });
    });

    // ── Right-click context menu ──
    // The browser's own context menu is suppressed everywhere on this page;
    // right-clicking an item instead opens a menu built from the same
    // do*() action functions the bulk bar and preview modal already use.
    $app.on('contextmenu', function (e) { e.preventDefault(); });

    function contextMenuHtml(ids, item) {
        if (state.scope === 'trash') {
            return `
                <div class="dms-context-item" data-action="restore"><i class="ri-arrow-go-back-line"></i> Restore</div>
                <div class="dms-context-divider"></div>
                <div class="dms-context-item danger" data-action="delete-forever"><i class="ri-delete-bin-7-line"></i> Delete Forever</div>
            `;
        }

        let html = '';

        if (ids.length === 1 && item) {
            html += item.kind === 'folder'
                ? `<div class="dms-context-item" data-action="open"><i class="ri-folder-open-line"></i> Open</div>`
                : `<div class="dms-context-item" data-action="preview"><i class="ri-eye-line"></i> Preview</div>
                   <div class="dms-context-item" data-action="download"><i class="ri-download-2-line"></i> Download</div>`;
            html += `<div class="dms-context-divider"></div>`;
            html += `<div class="dms-context-item" data-action="rename"><i class="ri-edit-line"></i> Rename</div>`;
            html += `<div class="dms-context-item" data-action="favorite"><i class="${item.favorite ? 'ri-star-fill' : 'ri-star-line'}"></i> ${item.favorite ? 'Remove from Favorites' : 'Add to Favorites'}</div>`;
        } else {
            html += `<div class="dms-context-item" data-action="download"><i class="ri-download-2-line"></i> Download (${ids.length})</div>`;
        }

        html += `<div class="dms-context-divider"></div>`;
        html += `<div class="dms-context-item" data-action="move"><i class="ri-folder-transfer-line"></i> Move to&hellip;</div>`;
        html += `<div class="dms-context-divider"></div>`;
        html += `<div class="dms-context-item danger" data-action="delete"><i class="ri-delete-bin-line"></i> Delete</div>`;

        return html;
    }

    function closeContextMenu() {
        $('#dmsContextMenu').removeClass('open');
    }

    function openContextMenu(e, el) {
        const id = Number($(el).data('id'));

        if (!state.selected.has(id)) {
            state.selected.clear();
            state.selected.add(id);
            updateSelectionUi();
            renderBulkbar();
        }

        contextMenuIds = [...state.selected];
        contextMenuItem = findLoadedItem(id);

        const $menu = $('#dmsContextMenu');
        $menu.html(contextMenuHtml(contextMenuIds, contextMenuItem)).addClass('open').css({ top: e.clientY, left: e.clientX });

        // Clamp inside the viewport so the menu never renders off-screen.
        const rect = $menu[0].getBoundingClientRect();
        if (rect.right > window.innerWidth) $menu.css('left', Math.max(4, window.innerWidth - rect.width - 8));
        if (rect.bottom > window.innerHeight) $menu.css('top', Math.max(4, window.innerHeight - rect.height - 8));
    }

    $(document).on('contextmenu', '.dms-card, .dms-list-row, .dms-table tr[data-id]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openContextMenu(e, this);
    });

    $(document).on('click', '#dmsContextMenu [data-action]', function () {
        const action = $(this).data('action');
        const ids = contextMenuIds;
        const item = contextMenuItem;
        closeContextMenu();

        switch (action) {
            case 'open': openFolder(item.id); break;
            case 'preview': openPreview(item.id); break;
            case 'download': doDownload(ids); break;
            case 'rename': doRename(item.id); break;
            case 'favorite': doToggleFavorite(item.id); break;
            case 'move': openMoveModalFor(ids); break;
            case 'delete': doTrash(ids); break;
            case 'restore': doRestore(ids); break;
            case 'delete-forever': doDeleteForever(ids); break;
        }
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#dmsContextMenu').length) closeContextMenu();
    });
    $(document).on('keydown', function (e) { if (e.key === 'Escape') closeContextMenu(); });
    $(window).on('scroll resize', closeContextMenu);

    // ── Preview modal ──
    function previewMarkup(item) {
        if (item.type === 'image' && item.url) return `<img src="${item.url}" alt="${escapeHtml(item.name)}">`;
        if (item.type === 'video' && item.url) return `<video src="${item.url}" controls></video>`;
        if (item.type === 'audio' && item.url) return `<audio src="${item.url}" controls></audio>`;
        return `<i class="${item.icon}"></i>`;
    }

    function itemPath(item) {
        if (state.scope === 'all') {
            return '/' + state.breadcrumb.map(p => p.name).concat(item.name).join('/');
        }
        return '/' + item.name;
    }

    let activePreview = null;
    function openPreview(id) {
        const item = findLoadedItem(id);
        if (!item || item.kind === 'folder') return;

        activePreview = item;
        $('#dmsPvIcon').attr('class', 'dms-type-icon dms-type-' + item.type).html(`<i class="${item.icon}"></i>`);
        $('#dmsPvName').text(item.name);
        $('#dmsPvMeta').text(`${item.label} · ${item.size_formatted || '0 B'} · ${item.date_formatted || ''}`);
        $('#dmsPvPreview').html(previewMarkup(item));
        $('#dmsPvDownload').attr('href', item.download_url || '#');
        $('#dmsPreviewOverlay').addClass('open');
    }
    $('#dmsPvClose').on('click', () => $('#dmsPreviewOverlay').removeClass('open'));
    $('#dmsPreviewOverlay').on('click', function (e) { if (e.target.id === 'dmsPreviewOverlay') $(this).removeClass('open'); });

    $('.dms-copy-btn').on('click', function () {
        if (!activePreview) return;
        const item = activePreview;
        const kind = $(this).data('copy');
        let text = item.url || '';
        if (kind === 'path') text = itemPath(item);
        if (kind === 'html') text = `<img src="${item.url}" alt="${item.name}">`;
        if (kind === 'markdown') text = `![${item.name}](${item.url})`;
        navigator.clipboard?.writeText(text);
        toast('Copied ' + kind.toUpperCase());
    });

    // ── Init ──
    setView(state.view);
    applySort(state.sort);

    // Activates the shared #openModal remote-modal handler (main.js) for the
    // "New Folder" button — every DataTables-based page calls this from its
    // own drawCallback; DMS has no DataTable, so it's called once here.
    if (typeof _componentRemoteModalLoadAfterAjax === 'function') {
        _componentRemoteModalLoadAfterAjax();
    }

    fetchData();
})();
