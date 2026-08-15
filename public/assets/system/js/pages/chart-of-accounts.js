// The shared .ajax-form / #delete_item handlers in main.js call
// `dataTableInstance.ajax.reload(null, false)` on success. Chart of Accounts
// uses a tree view instead of a DataTable, so we reuse that same hook to
// just reload the page and re-render the tree with the latest data.
var dataTableInstance = {
    ajax: {
        reload: function () {
            window.location.reload();
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    _statusUpdate();

    var $tree = $('#chartOfAccountTree');

    // Expand/collapse a single node
    $tree.on('click', '.coa-toggle:not(.leaf)', function () {
        var $li = $(this).closest('.coa-node');
        $li.children('.coa-children').toggleClass('collapsed');
        $(this).find('i').toggleClass('ri-arrow-right-s-line ri-arrow-down-s-line');
    });

    // Expand all
    $('#expandAll').on('click', function () {
        $tree.find('.coa-children').removeClass('collapsed');
        $tree.find('.coa-toggle:not(.leaf) i').removeClass('ri-arrow-right-s-line').addClass('ri-arrow-down-s-line');
    });

    // Collapse all
    $('#collapseAll').on('click', function () {
        $tree.find('.coa-children').addClass('collapsed');
        $tree.find('.coa-toggle:not(.leaf) i').removeClass('ri-arrow-down-s-line').addClass('ri-arrow-right-s-line');
    });

    // Client-side search: show matching nodes and expand their ancestors
    $('#chartOfAccountSearch').on('keyup', function () {
        var term = $(this).val().trim().toLowerCase();

        if (!term) {
            $tree.find('.coa-node').removeClass('hidden-by-search');
            $tree.find('.coa-children').addClass('collapsed');
            $tree.find('.coa-toggle:not(.leaf) i').removeClass('ri-arrow-down-s-line').addClass('ri-arrow-right-s-line');
            return;
        }

        $tree.find('.coa-node').each(function () {
            var matches = $(this).data('name').toString().indexOf(term) !== -1;
            $(this).toggleClass('hidden-by-search', !matches);
        });

        // Reveal ancestors and expand them for every match
        $tree.find('.coa-node').not('.hidden-by-search').each(function () {
            $(this).parents('.coa-node').removeClass('hidden-by-search')
                .children('.coa-children').removeClass('collapsed')
                .siblings('.coa-node-row').find('.coa-toggle:not(.leaf) i')
                .removeClass('ri-arrow-right-s-line').addClass('ri-arrow-down-s-line');
        });
    });
});
