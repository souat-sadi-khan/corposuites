var dataTableInstance;

var DataTableWorkflowDefinitions = function () {
    var initDataTable = function () {
        if (!$().DataTable) {
            console.warn('DataTables not loaded');
            return;
        }

        dataTableInstance = $('#workflowDefinitionTable').DataTable({
            dom: 't',
            processing: true,
            serverSide: true,
            pageLength: 9,
            lengthChange: false,
            searching: true,
            order: [[0, 'desc']],
            ajax: {
                url: $('#workflowDefinitionTable').data('url'),
                data: function (d) {
                    d.search = $('#workflowDefinitionSearch').val();
                    var statuses = [];
                    $('#tlFilterDd input:checked').each(function () {
                        statuses.push($(this).val());
                    });
                    if (statuses.length) {
                        d.status = statuses.join(',');
                    }
                }
            },
            columns: [
                { data: 'id', visible: false },
                { data: 'module_label' },
                { data: 'name' },
                { data: 'approval_mode_badge' },
                { data: 'steps_count_badge' },
                { data: 'status_badge' },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-end'
                }
            ],
            language: {
                emptyTable: `
                    <div class="text-center py-4">
                        <img src="${window.location.origin}/assets/images/nothing-to-show.png" class="img-fluid mb-2" style="max-width:150px">
                        <p class="text-muted mb-0">No workflow definitions available</p>
                    </div>
                `
            },
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip();
                updateTlInfo();
                _componentSwitch();
                if (typeof _componentRemoteModalLoadAfterAjax === 'function') {
                    _componentRemoteModalLoadAfterAjax();
                }
                _componentSwitch();
            }
        });
    };

    return {
        init: function () {
            initDataTable();
            _statusUpdate();
        }
    };
}();

// =====================================================
// Dynamic Workflow Step / Approver Rows (event-delegated
// so it works regardless of when the modal form is injected)
// =====================================================
var workflowStepRowIndex = 0;

function approverOptionsHtml(form, type) {
    return form.find('.workflow-approver-options[data-type="' + type + '"]').html();
}

function buildWorkflowApproverRow(container, form, approverType, approverId) {
    var stepIndex = container.closest('.workflow-step-row').data('step-index');
    var approverIndex = container.data('approver-index') || 0;
    container.data('approver-index', approverIndex + 1);

    var type = approverType || 'role';
    var optionsHtml = approverOptionsHtml(form, type);

    var row = $(`
        <div class="fm-grid workflow-approver-row mb-2">
            <div class="fm-field">
                <select name="steps[${stepIndex}][approvers][${approverIndex}][approver_type]" class="form-select workflow-approver-type">
                    <option value="role" ${type === 'role' ? 'selected' : ''}>Role</option>
                    <option value="user" ${type === 'user' ? 'selected' : ''}>User</option>
                    <option value="designation" ${type === 'designation' ? 'selected' : ''}>Designation</option>
                </select>
            </div>
            <div class="fm-field">
                <select name="steps[${stepIndex}][approvers][${approverIndex}][approver_id]" class="form-select workflow-approver-id">${optionsHtml}</select>
            </div>
            <div class="fm-field">
                <button type="button" class="btn-nx-outline btn-sm remove-workflow-approver">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        </div>
    `);

    if (approverId) {
        row.find('.workflow-approver-id').val(approverId);
    }

    container.append(row);
}

function buildWorkflowStepRow(container, stepData) {
    var form = container.closest('form');
    var stepIndex = workflowStepRowIndex++;

    var row = $(`
        <div class="nx-card workflow-step-row mb-3 p-3" data-step-index="${stepIndex}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Step ${stepIndex + 1}</strong>
                <button type="button" class="btn-nx-outline btn-sm remove-workflow-step">
                    <i class="ri-delete-bin-line"></i> Remove Step
                </button>
            </div>
            <div class="fm-grid">
                <div class="fm-field">
                    <label>Step Name <span class="req">*</span></label>
                    <input type="text" class="form-control" name="steps[${stepIndex}][name]" placeholder="e.g., Manager Approval" required>
                </div>
                <div class="fm-field">
                    <label>Approval Type <span class="req">*</span></label>
                    <select name="steps[${stepIndex}][approval_type]" class="form-select" required>
                        <option value="single">Single</option>
                        <option value="all_must_approve">All Must Approve</option>
                        <option value="any_one_approves">Any One Approves</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                <label class="mb-0 small">Approvers <span class="req">*</span></label>
                <button type="button" class="btn-nx-outline btn-sm workflow-approver-add">
                    <i class="ri-add-line"></i> Add Approver
                </button>
            </div>
            <div class="workflow-approver-rows"></div>
        </div>
    `);

    container.append(row);

    if (stepData) {
        row.find('input[name^="steps"][name$="[name]"]').val(stepData.name || '');
        row.find('select[name$="[approval_type]"]').val(stepData.approval_type || 'single');

        var approverContainer = row.find('.workflow-approver-rows');
        (stepData.approvers || []).forEach(function (approver) {
            buildWorkflowApproverRow(approverContainer, form, approver.approver_type, approver.approver_id);
        });
    }
}

$(document).on('click', '.workflow-step-add', function () {
    var container = $(this).closest('.modal-body, .offcanvas-body').find('.workflow-step-rows');
    buildWorkflowStepRow(container);
});

$(document).on('click', '.remove-workflow-step', function () {
    $(this).closest('.workflow-step-row').remove();
});

$(document).on('click', '.workflow-approver-add', function () {
    var stepRow = $(this).closest('.workflow-step-row');
    var container = stepRow.find('.workflow-approver-rows');
    var form = stepRow.closest('form');
    buildWorkflowApproverRow(container, form);
});

$(document).on('click', '.remove-workflow-approver', function () {
    $(this).closest('.workflow-approver-row').remove();
});

// When the approver type changes, swap the approver_id options to match.
$(document).on('change', '.workflow-approver-type', function () {
    var select = $(this);
    var form = select.closest('form');
    var idSelect = select.closest('.workflow-approver-row').find('.workflow-approver-id');
    idSelect.html(approverOptionsHtml(form, select.val()));
});

// Populate existing steps/approvers when the edit form is injected into the modal.
function populateExistingWorkflowSteps(scope) {
    $(scope).find('.workflow-step-rows[data-existing]').each(function () {
        var container = $(this);
        if (container.data('populated')) return;
        container.data('populated', true);

        var existing = [];
        try {
            existing = JSON.parse(container.attr('data-existing')) || [];
        } catch (e) {
            existing = [];
        }

        existing.forEach(function (step) {
            buildWorkflowStepRow(container, step);
        });
    });
}

(function observeModalContent() {
    var modalContent = document.querySelector('#modal_remote .modal-content');
    if (!modalContent || typeof MutationObserver === 'undefined') return;

    new MutationObserver(function () {
        populateExistingWorkflowSteps(modalContent);
    }).observe(modalContent, { childList: true, subtree: true });
})();

// Client-side guard: require at least one step with at least one approver before submit.
$(document).on('submit', '.workflow-definition-form', function (e) {
    var steps = $(this).find('.workflow-step-row');
    if (steps.length === 0) {
        e.preventDefault();
        e.stopImmediatePropagation();
        alert('Add at least one approval step.');
        return false;
    }

    var invalid = false;
    steps.each(function () {
        if ($(this).find('.workflow-approver-row').length === 0) {
            invalid = true;
        }
    });

    if (invalid) {
        e.preventDefault();
        e.stopImmediatePropagation();
        alert('Every step needs at least one approver.');
        return false;
    }
});

// =====================================================
// Pagination Info
// =====================================================
function updateTlInfo() {
    var info = dataTableInstance.page.info();
    var start = info.recordsDisplay === 0 ? 0 : info.start + 1;
    $('#tlInfo').text(start + ' - ' + info.end + ' of ' + info.recordsDisplay);
    $('#tlPrev').prop('disabled', info.page === 0);
    $('#tlNext').prop('disabled', info.page >= info.pages - 1 || info.pages === 0);
}

// =====================================================
// Document Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    DataTableWorkflowDefinitions.init();

    // Search
    $('#workflowDefinitionSearch').on('keyup', function () {
        dataTableInstance.draw();
    });

    // Previous / Next
    $('#tlPrev').on('click', function () {
        dataTableInstance.page('previous').draw('page');
    });
    $('#tlNext').on('click', function () {
        dataTableInstance.page('next').draw('page');
    });

    // Filter dropdown
    $('#tlFilterBtn').on('click', function (e) {
        e.stopPropagation();
        $('#tlFilterDd').toggleClass('is-open');
    });
    $('#tlFilterDd').on('click', function (e) {
        e.stopPropagation();
    });
    $(document).on('click', function () {
        $('#tlFilterDd').removeClass('is-open');
    });
    $('#tlFilterDd input').on('change', function () {
        dataTableInstance.draw();
    });
});
