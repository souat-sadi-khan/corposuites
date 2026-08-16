'use strict';

/* =========================
    GLOBAL AJAX CONFIG
========================= */

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || ''
    }
});

/* =========================
    SELECT2
========================= */

function _selectOptionTemplate(option) {
    if (!option.id || !option.element) {
        return option.text;
    }

    var desc = $(option.element).data('desc');
    if (!desc) {
        return option.text;
    }

    var $opt = $(
        '<div class="sel-opt-rich">' +
            '<div class="sel-opt-rich-name"></div>' +
            '<div class="sel-opt-rich-desc"></div>' +
        '</div>'
    );
    $opt.find('.sel-opt-rich-name').text(option.text);
    $opt.find('.sel-opt-rich-desc').text(desc);

    return $opt;
}

function _componentSelect() {
    $('.select').select2({
        width: '100%',
        templateResult: _selectOptionTemplate
    });
}

function _componentSelectOffCanvas() {
    $('.select').select2({
        width: '100%',
        dropdownParent: $('#sideForm .offcanvas-content'),
        templateResult: _selectOptionTemplate
    });
}

function _componentSelect2Modal() {
    $('.select').select2({
        width: '100%',
        dropdownParent: $('#modal_remote')
    });
}

// Switch
var _componentSwitch = function() {
    var elems = document.querySelectorAll('.switch');

    elems.forEach(function(elem) {
        if (!elem.classList.contains('switchery-initialized')) {
            new Switchery(elem, { color: '#8854f1', size: 'small' });
            elem.classList.add('switchery-initialized');
        }

    });
}

/*
 * For Updating Status
 */
var _statusUpdate = function(){
    $(document).on('change', 'input[name="status"]', function() {
        var status = this.checked ? 1 : 0;
        var url = $(this).data('url');

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                status: status
            },
            success: function(response) {
                if (response.success) {
                    Lobibox.notify('success', {
                        size: 'mini',
                        rounded: true,
                        icon: 'ri-checkbox-circle-line',
                        position: 'bottom right',
                        msg: response.message
                    });
                } else {
                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        icon: 'ri-close-circle-line',
                        position: 'bottom right',
                        msg: response.message
                    });
                }
            },
            error: function(xhr) {
                Lobibox.notify('error', {
                    size: 'mini',
                    rounded: true,
                    icon: 'ri-close-circle-line',
                    position: 'bottom right',
                    msg: "An error occurred while updating the status"
                });
            }
        });
    });
};

/* =========================
    DROPIFY
========================= */

function _componentDropify() {
    $('.dropify').dropify();
}

/* =========================
    FORM HANDLER (AJAX CORE)
========================= */

function _ajaxFormHandler(formClass) {

    if (!$(formClass).length) return;

    $(formClass).each(function () {

        var $form = $(this);

        $form.on('blur change input', 'input, textarea, select', function () {
            validateField($(this), $form);
        });

        $form.on('submit', function (e) {
            e.preventDefault();

            $form.find('.ajax_error').remove();

            let valid = true;

            $form.find("input, textarea, select").each(function () {
                if (!validateField($(this), $form)) valid = false;
            });

            if (!valid) {
                $form.find(".is-invalid:first").focus();
                return false;
            }

            var $submitBtn = $form.find("button[type=submit]");
            var originalText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Submitting...');

            var formData = new FormData($form[0]);

            $.ajax({
                url: $form.attr('action'),
                type: $form.attr('method') || 'POST',
                data: formData,
                contentType: false,
                cache: false,
                processData: false,
                dataType: 'json',

                beforeSend: function () {
                    $('#submit').hide();
                    $('#submitting').show();
                },

                success: function (res) {

                    $('#submitting').hide();
                    $('#submit').show();

                    if (res.status) {

                        Lobibox.notify('success', {
                            size: 'mini',
                            rounded: true,
                            icon: 'ri-checkbox-circle-line',
                            position: 'bottom right',
                            msg: res.message
                        });

                        $form[0].reset();
                        $form.find('.is-valid').removeClass('is-valid');

                        if (res.goto) {
                            setTimeout(() => window.location.href = res.goto, 1200);
                        }

                        if (res.load) {
                            setTimeout(() => location.reload(), 1200);
                        }

                    } else {

                        Lobibox.notify('error', {
                            size: 'mini',
                            rounded: true,
                            icon: 'ri-close-circle-line',
                            position: 'bottom right',
                            msg: res.message
                        });

                        if (res.message) {
                            Object.values(res.errors).forEach(messages => {
                                messages.forEach(msg => {
                                    Lobibox.notify('error', {
                                        size: 'mini',
                                        rounded: true,
                                        icon: 'ri-close-circle-line',
                                        position: 'bottom right',
                                        msg: msg
                                    });
                                });
                            });
                        }
                    }

                    $submitBtn.prop('disabled', false).text(originalText);
                },

                error: function (err) {

                    $('#submitting').hide();
                    $('#submit').show();

                    let jsonValue = {};

                    try {
                        jsonValue = JSON.parse(err.responseText);
                    } catch (e) {
                        jsonValue = { message: 'Something went wrong!' };
                    }

                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        icon: 'ri-close-circle-line',
                        position: 'bottom right',
                        msg: jsonValue.message
                    });

                    if (jsonValue.errors) {
                        Object.values(jsonValue.errors).forEach(messages => {
                            messages.forEach(msg => {
                                Lobibox.notify('error', {
                                    size: 'mini',
                                    rounded: true,
                                    icon: 'ri-close-circle-line',
                                    position: 'bottom right',
                                    msg: msg
                                });
                            });
                        });
                    }

                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });

        function validateField($el, $form) {

            if ($el.is(':hidden')) return true;

            var val = ($el.val() || '').toString().trim();
            var type = $el.attr('type');
            var name = $el.attr('name');

            if ($el.prop('required') && !val) {
                setInvalid($el, 'This field is required.');
                return false;
            }

            if (type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                setInvalid($el, 'Enter a valid email address.');
                return false;
            }

            if (type === 'number' && val && isNaN(val)) {
                setInvalid($el, 'Enter a valid number.');
                return false;
            }

            if (type === 'password' && val && val.length < 8) {
                setInvalid($el, 'Password must be at least 8 characters.');
                return false;
            }

            if ((type === 'checkbox' || type === 'radio') && $el.prop('required')) {
                var checked = $form.find("[name='" + name + "']:checked").length;
                if (!checked) {
                    setInvalid($el, 'This option is required.');
                    return false;
                }
            }

            setValid($el);
            return true;
        }

        function setValid($el, msg) {
            $el.removeClass('is-invalid').addClass('is-valid');
            var $fb = $el.siblings('.valid-feedback');
            if ($fb.length) $fb.text(msg || 'Looks good!');
        }

        function setInvalid($el, msg) {
            $el.removeClass('is-valid').addClass('is-invalid');
            var $fb = $el.siblings('.invalid-feedback');
            if ($fb.length) $fb.text(msg || 'This field is required.');
        }

    });
}

var modalLoader = `
<div class="modal-header border-0 pb-0">
    <div class="placeholder-glow w-100">
        <span class="placeholder col-6"></span>
    </div>
</div>

<div class="modal-body">
    <div class="placeholder-glow">
        <span class="placeholder col-12 mb-2"></span>
        <span class="placeholder col-10 mb-2"></span>
        <span class="placeholder col-8 mb-2"></span>
        <span class="placeholder col-12 mb-2"></span>
        <span class="placeholder col-7"></span>
    </div>
</div>
`;

// For Opening Modal
var _componentRemoteModalLoadAfterAjax = function () {
    $(document)
        .off('click', '#openModal')
        .on('click', '#openModal', function (e) {

            e.preventDefault();

            var url = $(this).data('url');

            $('.modal-content').html(modalLoader);

            var modal = new bootstrap.Modal(document.getElementById('modal_remote'));

            modal.show();

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'html',
                cache: false
            })
            .done(function(res){

                $('.modal-content')
                    .hide()
                    .html(res)
                    .fadeIn(180);

                _componentSelect2Modal();
                _modalFormValidation();
            })
            .fail(function(){

                $('.modal-content').html(`
                    <div class="p-5 text-center">
                        <i class="ri-error-warning-line fs-1 text-danger"></i>
                        <div class="mt-3">
                            Something went wrong.
                        </div>
                    </div>
                `);

            });

        });

};

/* =========================
    OFFCANVAS LOADER
========================= */

function _componentRemoteOffcanvasLoadAfterAjax() {

    $(document)
        .off('click', '.side-offcanvas')
        .on('click', '.side-offcanvas', function (e) {

            e.preventDefault();

            const url = $(this).data('url');
            const width = $(this).data('width');

            const offcanvasEl = document.getElementById('sideForm');

            if (!offcanvasEl) {
                console.error('Offcanvas #sideForm not found.');
                return;
            }

            // Dynamic Width
            if (width) {
                offcanvasEl.style.setProperty('--offcanvas-width', width);
            }

            // Loader
            $('#offcanvas-loader').show();
            $('#sideForm .offcanvas-content').empty();

            // Bootstrap Offcanvas
            const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
            offcanvas.show();

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'html',

                success: function (response) {

                    $('#sideForm .offcanvas-content').html(response);

                    $('#offcanvas-loader').hide();
                    _componentDropify();
                    
                    _componentSelectOffCanvas();
                    if (typeof initFormPlugins === 'function') {
                        initFormPlugins(offcanvasEl);
                    }

                    if (typeof _modalClassFormValidation === 'function') {
                        _modalClassFormValidation();
                    }

                },

                error: function () {

                    $('#sideForm .offcanvas-content').html(
                        '<div class="p-4 text-danger">Unable to load content.</div>'
                    );

                    $('#offcanvas-loader').hide();
                }

            });

        });

}

/* =========================
    DELETE HANDLER
========================= */

$(document).on('click', '#delete_item', function (e) {

    e.preventDefault();

    var url = $(this).data('url');

    Swal.fire({
        title: 'Are you sure?',
        text: "This cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33'
    }).then((result) => {

        if (!result.isConfirmed) return;

        $.ajax({
            url: url,
            method: 'DELETE',
            dataType: 'json',

            success: function (data) {

                if (data.status) {

                    Lobibox.notify('success', {
                        msg: data.message
                    });

                    if (dataTableInstance) {
                        dataTableInstance.ajax.reload(null, false);
                    }
                } else {
                    Lobibox.notify('error', { msg: data.message });
                }
            }
        });
    });
});

/* =========================
    MODAL FORM VALIDATION
========================= */

function _modalClassFormValidation() {
    $('.ajax-form').each(function () {
        var $form = $(this);

        // Initialize Parsley
        if ($form.length) {
            $form.parsley();
        }

        $form.on('submit', function (e) {

            e.preventDefault();

            var form = this;

            var $submitBtn = $form.find('#submit');
            var $submittingBtn = $form.find('#submitting');

            // Client side validation
            if (!$form.parsley().isValid()) {
                return false;
            }

            $submitBtn.hide();
            $submittingBtn.show();

            $.ajax({

                url: $form.attr('action'),
                type: 'POST',

                data: new FormData(form),

                contentType: false,
                processData: false,
                dataType: 'json',

                success: function (response) {

                    $submitBtn.show();
                    $submittingBtn.hide();

                    if (response.status === true) {
                        if (typeof Lobibox !== 'undefined') {
                            Lobibox.notify('success', {
                                msg: response.message || 'Operation completed successfully.',
                                position: 'top right'
                            });

                        } else {
                            alert(response.message || 'Success');
                        }

                        // Close modal/offcanvas
                        if (!response.stay) {
                            var offcanvasEl = document.getElementById('sideForm');
                            bootstrap.Offcanvas.getInstance(offcanvasEl)?.hide();
                        }

                        // Reload datatable
                        if (typeof dataTableInstance !== 'undefined' && dataTableInstance) {
                            dataTableInstance.ajax.reload(null, false);
                        }

                        // Reset form
                        if (response.reset) {
                            form.reset();
                            $form.parsley().reset();
                        }
                    } else {
                        showFormErrors(response);
                    }
                },
                error: function (xhr) {
                    $submitBtn.show();
                    $submittingBtn.hide();

                    let response = xhr.responseJSON;
                    if (response) {
                        showFormErrors(response);
                    } else {
                        notifyError('Something went wrong.');
                    }
                }
            });
        });
    });
}

function showFormErrors(response)
{
    let message = response.message || 'Validation failed.';

    // Main message
    notifyError(message);

    // Field errors
    if(response.errors)
    {
        $.each(response.errors, function(field, messages){
            $.each(messages, function(index, msg){
                notifyError(msg);
            });
        });
    }
}

function notifyError(message)
{
    if(typeof Lobibox !== 'undefined') {
        Lobibox.notify('error', {
            size: 'mini',
            rounded: true,
            icon: 'ri-close-circle-line',
            position: 'bottom right',
            msg: message

        });

    } else {
        alert(message);
    }
}

/**
 * Modal / Offcanvas Form Validation & Submission
 * Uses Parsley for client-side validation and AJAX for submission.
 * Designed for offcanvas forms with class 'ajax-form'.
 */
function _modalFormValidation() {

    // Initialize Parsley on all .ajax-form within offcanvas
    if ($('.ajax-form').length) {
        // Destroy any existing instances first to avoid duplicates
        $('.ajax-form').parsley().destroy();
        $('.ajax-form').parsley({
            errorsWrapper: '<div class="invalid-feedback"></div>',
            errorTemplate: '<span></span>',
            classHandler: function (el) {
                return el.$element.closest('.fm-field, .form-group, .mb-3');
            },
            errorsContainer: function (el) {
                return el.$element.closest('.fm-field, .form-group, .mb-3').find('.invalid-feedback');
            }
        });
    }

    // Handle form submission via event delegation (for dynamically loaded forms)
    $(document).off('submit', '.ajax-form').on('submit', '.ajax-form', function (e) {
        e.preventDefault();

        var $form = $(this);
        var parsley = $form.parsley();

        // Validate the form
        if (!parsley.validate()) {
            // Focus the first invalid field
            var $firstError = $form.find('.parsley-error:first');
            if ($firstError.length) {
                $firstError.focus();
            }
            return;
        }

        // Get submit and submitting buttons (using IDs, but fallback to button[type="submit"])
        var $submitBtn = $form.find('#submit, button[type="submit"]:not([disabled])');
        var $submittingBtn = $form.find('#submitting');

        // Toggle button states
        $submitBtn.hide();
        $submittingBtn.show();

        // Prepare FormData (supports file uploads)
        var formData = new FormData(this);

        // Get CSRF token from meta tag
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function (response) {
                // Restore buttons
                $submitBtn.show();
                $submittingBtn.hide();

                if (response.status) {
                    // Success notification (using Lobibox if available)
                    if (typeof Lobibox !== 'undefined') {
                        Lobibox.notify('success', {
                            msg: response.message || 'Operation completed successfully.',
                            position: 'top right'
                        });
                    } else {
                        // Fallback to native alert or custom toast
                        alert(response.message || 'Success!');
                    }

                    // Close the offcanvas unless 'stay' flag is true
                    if (!response.stay) {
                        $('#modal_remote').modal('hide');
                    }

                    // Reload DataTable if it exists globally
                    if (typeof dataTableInstance !== 'undefined' && dataTableInstance) {
                        dataTableInstance.ajax.reload(null, false);
                    }

                    // Optionally reset the form
                    if (response.reset) {
                        $form[0].reset();
                        parsley.reset();
                    }
                } else {
                    // Server returned status false
                    if (typeof Lobibox !== 'undefined') {
                        Lobibox.notify('error', {
                            msg: response.message || 'An error occurred.',
                            position: 'top right'
                        });

                        if (response.errors) {
                            Object.values(response.errors).forEach(messages => {
                                messages.forEach(msg => {
                                    Lobibox.notify('error', {
                                        size: 'mini',
                                        rounded: true,
                                        icon: 'ri-close-circle-line',
                                        position: 'bottom right',
                                        msg: msg
                                    });
                                });
                            });
                        }
                    } else {
                        alert(response.message || 'Error!');
                    }
                }
            },
            error: function (xhr) {
                // Restore buttons
                $submitBtn.show();
                $submittingBtn.hide();

                // Handle validation errors (HTTP 422)
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    // Show first error in notification
                    var firstError = Object.values(errors)[0][0];
                    if (typeof Lobibox !== 'undefined') {
                        Lobibox.notify('error', {
                            msg: firstError || 'Validation error.',
                            position: 'top right'
                        });
                    } else {
                        alert(firstError || 'Validation error.');
                    }

                    // Optionally, display errors on fields (custom mapping)
                    // You can loop through errors and add class to fields if needed
                } else {
                    // Generic error
                    var errorMsg = xhr.responseJSON?.message || 'An unexpected error occurred.';
                    if (typeof Lobibox !== 'undefined') {
                        Lobibox.notify('error', {
                            msg: errorMsg,
                            position: 'top right'
                        });
                    } else {
                        alert(errorMsg);
                    }
                }
            }
        });
    });
}

// Logout
function logout() {
    let url = document.getElementById('logout').dataset.url;

    Swal.fire({
        title: 'Sign Out?',
        text: 'You will be logged out from your account.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Sign Out',
        cancelButtonText: 'Cancel'
    }).then((result) => {

        if (!result.isConfirmed) {
            return;
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',

            success: function (response) {

                Lobibox.notify('success', {
                    size: 'mini',
                    rounded: true,
                    icon: 'bx bx-check-circle',
                    position: 'bottom right',
                    msg: response.message
                });

                setTimeout(function () {
                    window.location.href = response.goto;
                }, 1000);

            },

            error: function (xhr) {

                let message = 'Something went wrong.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                Lobibox.notify('error', {
                    size: 'mini',
                    rounded: true,
                    position: 'bottom right',
                    msg: message
                });

            }

        });

    });

}

$(document).on('click','#optimizeBtn',function(){
    let btn = $(this);
    let icon = btn.find('i');

    if(btn.hasClass('loading')){
        return;
    }

    btn.addClass('loading');

    icon
        .removeClass('ri-refresh-line')
        .addClass('ri-loader-4-line');

    $.ajax({

        url:"/admin/system/optimize",
        type:"POST",
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success:function(response){
            if(response.status){
                Lobibox.notify('success',{
                    msg:response.message,
                    position:'top right'
                });
            } else {
                Lobibox.notify('error',{

                    msg:response.message,
                    position:'top right'

                });
            }
        },
        error:function(xhr){
            Lobibox.notify('error',{
                msg:'Optimization failed.',
                position:'top right'
            });
        },
        complete:function(){
            btn.removeClass('loading');
            icon
                .removeClass('ri-loader-4-line')
                .addClass('ri-refresh-line');
        }
    });
});
