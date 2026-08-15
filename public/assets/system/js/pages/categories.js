// The shared .ajax-form / #delete_item handlers in main.js call
// `dataTableInstance.ajax.reload(null, false)` on success. Categories uses a
// tree view instead of a DataTable, so we reuse that same hook to just
// reload the page and re-render the tree with the latest data.
var dataTableInstance = {
    ajax: {
        reload: function () {
            window.location.reload();
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    _statusUpdate();

    var $tree = $('#categoryTree');

    // Expand/collapse a single node
    $tree.on('click', '.cat-toggle:not(.leaf)', function () {
        var $li = $(this).closest('.cat-node');
        $li.children('.cat-children').toggleClass('collapsed');
        $(this).find('i').toggleClass('ri-arrow-right-s-line ri-arrow-down-s-line');
    });

    // Expand all
    $('#expandAll').on('click', function () {
        $tree.find('.cat-children').removeClass('collapsed');
        $tree.find('.cat-toggle:not(.leaf) i').removeClass('ri-arrow-right-s-line').addClass('ri-arrow-down-s-line');
    });

    // Collapse all
    $('#collapseAll').on('click', function () {
        $tree.find('.cat-children').addClass('collapsed');
        $tree.find('.cat-toggle:not(.leaf) i').removeClass('ri-arrow-down-s-line').addClass('ri-arrow-right-s-line');
    });

    // Client-side search: show matching nodes and expand their ancestors
    $('#categorySearch').on('keyup', function () {
        var term = $(this).val().trim().toLowerCase();

        if (!term) {
            $tree.find('.cat-node').removeClass('hidden-by-search');
            $tree.find('.cat-children').addClass('collapsed');
            $tree.find('.cat-toggle:not(.leaf) i').removeClass('ri-arrow-down-s-line').addClass('ri-arrow-right-s-line');
            return;
        }

        $tree.find('.cat-node').each(function () {
            var matches = $(this).data('name').toString().indexOf(term) !== -1;
            $(this).toggleClass('hidden-by-search', !matches);
        });

        // Reveal ancestors and expand them for every match
        $tree.find('.cat-node').not('.hidden-by-search').each(function () {
            $(this).parents('.cat-node').removeClass('hidden-by-search')
                .children('.cat-children').removeClass('collapsed')
                .siblings('.cat-node-row').find('.cat-toggle:not(.leaf) i')
                .removeClass('ri-arrow-right-s-line').addClass('ri-arrow-down-s-line');
        });
    });
});
