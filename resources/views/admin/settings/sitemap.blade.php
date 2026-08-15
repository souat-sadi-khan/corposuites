@extends('admin.layout.app', ['title' => t('site_title.sitemap')])

@push('styles')
    <style>
        .tree-node > .tree-children {
            display: none;
            margin-left: 25px;
        }

        .tree-node.is-open > .tree-children {
            display: block;
        }

        .tree-caret {
            width: 25px;
            height: 25px;
            border: 0;
            background: none;
            cursor: pointer;
        }

        .tree-caret i {
            transition: .2s;
        }

        .tree-node.is-open > .tree-row .tree-caret i {
            transform: rotate(90deg);
        }

        .tree-node.is-filtered-out {
            display: none;
        }

        .tree-row.is-match {
            background: #eef6ff;
        }

        .tree-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 6px;
            border-radius: 6px;
            cursor: pointer;
        }

        .tree-row:hover {
            background: #f5f6f8;
        }

        .tree-icon {
            font-size: 14px;
            color: #9496a8;
            flex-shrink: 0;
        }

        .tree-label {
            font-weight: 600;
            font-size: 13px;
        }

        .tree-path {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: #9496a8;
        }

        .tree-method {
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 99px;
            text-transform: uppercase;
        }

        .method-get    { background: rgba(2,132,199,.10);  color: #0284c7; }
        .method-post   { background: rgba(22,163,74,.10);  color: #16a34a; }
        .method-put    { background: rgba(217,119,6,.10);  color: #d97706; }
        .method-patch  { background: rgba(217,119,6,.10);  color: #d97706; }
        .method-delete { background: rgba(220,38,38,.10);  color: #dc2626; }

        .tree-row-actions {
            margin-left: auto;
        }

        .tra-btn {
            width: 24px;
            height: 24px;
            border: none;
            background: none;
            color: #9496a8;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .tra-btn:hover {
            background: rgba(79,82,232,.10);
            color: #4f52e8;
        }

        .tree-toolbar,
        .tree-legend {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            border-bottom: 1px solid #eceef2;
            flex-wrap: wrap;
        }

        .tree-search {
            position: relative;
            flex: 1;
            min-width: 160px;
        }

        .tree-search i {
            position: absolute;
            left: 9px;
            top: 50%;
            transform: translateY(-50%);
            color: #9496a8;
            font-size: 13px;
        }

        .tree-search input {
            width: 100%;
            border: 1px solid #e2e5ea;
            background: #f3f4f7;
            border-radius: 7px;
            padding: 6px 10px 6px 28px;
            font-size: 12.5px;
            outline: none;
        }

        .tl-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: #9496a8;
            font-weight: 600;
        }

        .tl-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
        }

        .tree-body {
            padding: 12px 12px 18px;
            max-height: 640px;
            overflow-y: auto;
        }

        ul.tree-root,
        ul.tree-children {
            list-style: none;
            padding-left: 0;
        }
    </style>
@endpush

@section('content')
    <div class="nx-card">

        <div class="nx-card-hdr">
            <div>
                <div class="nx-card-title">
                    <i class="ri-node-tree me-2"></i>
                    Sitemap
                </div>
                <div class="nx-card-sub">
                    Full page hierarchy · click a row to expand
                </div>
            </div>

            <div class="d-flex gap-1">
                <button class="btn-nx-ghost" id="expandAllBtn">
                    <i class="ri-expand-height-line"></i>
                    Expand all
                </button>
                <button class="btn-nx-ghost" id="collapseAllBtn">
                    <i class="ri-collapse-diagonal-line"></i>
                    Collapse all
                </button>
            </div>
        </div>

        <div class="tree-toolbar">
            <div class="tree-search">
                <i class="ri-search-line"></i>
                <input type="text" id="treeSearchInput" placeholder="Search routes">
            </div>
        </div>

        <div class="tree-legend">
            <span class="tl-item"><span class="tl-dot bg-success"></span>Published</span>
            <span class="tl-item"><span class="tl-dot bg-warning"></span>Draft</span>
            <span class="tl-item"><span class="tl-dot bg-secondary"></span>Archived</span>
        </div>

        <div class="tree-body" id="treeBody">
            <ul class="tree-root" id="treeRoot">
                @foreach ($tree as $key => $node)
                    @include('admin.settings.sitemap-node', [
                        'name' => $key,
                        'node' => $node,
                    ])
                @endforeach
            </ul>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        $(function () {

            /*
            |--------------------------------------------------------------------
            | Toggle node
            |--------------------------------------------------------------------
            */
            $('#treeBody').on('click', '.tree-row', function (e) {
                if ($(e.target).closest('.tra-btn').length) {
                    return;
                }

                var node = $(this).closest('.tree-node');

                if (node.attr('data-haschildren') === 'true') {
                    node.toggleClass('is-open');
                }
            });

            /*
            |--------------------------------------------------------------------
            | Expand / collapse all
            |--------------------------------------------------------------------
            */
            $('#expandAllBtn').on('click', function () {
                $('.tree-node[data-haschildren="true"]').addClass('is-open');
            });

            $('#collapseAllBtn').on('click', function () {
                $('.tree-node[data-haschildren="true"]').removeClass('is-open');
            });

            /*
            |--------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------
            | Matches against the row's own text (not its children's), so a
            | match on a parent doesn't get masked by a non-matching child,
            | and a match on a child correctly opens/keeps open its ancestors.
            */
            $('#treeSearchInput').on('keyup', function () {
                var keyword = $(this).val().toLowerCase().trim();

                $('.tree-node')
                    .removeClass('is-filtered-out')
                    .children('.tree-row')
                    .removeClass('is-match');

                if (!keyword) {
                    return;
                }

                $('.tree-node').addClass('is-filtered-out');

                $('.tree-node').each(function () {
                    var node = $(this);
                    // Only this node's own row text — not descendants —
                    // so we don't accidentally "match" every ancestor
                    // of every node in the tree.
                    var ownText = node.children('.tree-row').text().toLowerCase();

                    if (ownText.indexOf(keyword) !== -1) {
                        node.removeClass('is-filtered-out');
                        node.children('.tree-row').addClass('is-match');

                        // Reveal + open every ancestor so the match is visible.
                        node.parents('.tree-node').removeClass('is-filtered-out').addClass('is-open');

                        // Reveal descendants too (so a matched parent still
                        // shows its children rather than hiding them).
                        node.find('.tree-node').removeClass('is-filtered-out');
                    }
                });
            });

        });
    </script>
@endpush
