$(function () {
    'use strict';

    /* =========================
        HELPERS
    ========================= */

    const trim = (v) => (v || '').toString().trim();

    function setValid($input, msgId) {
        $input.removeClass('is-invalid').addClass('is-valid');
        $('#' + msgId).removeClass('error hint').addClass('ok')
            .html('<i class="ri-check-line"></i> Looks good');
    }

    function setInvalid($input, msgId, msg) {
        $input.removeClass('is-valid').addClass('is-invalid');
        $('#' + msgId).removeClass('ok hint').addClass('error')
            .html('<i class="ri-error-warning-line"></i> ' + msg);
    }

    function clearState($input, msgId) {
        $input.removeClass('is-valid is-invalid');
        $('#' + msgId).removeClass('ok error hint').text('').hide();
    }

    /* =========================
        PASSWORD TOGGLE
    ========================= */

    function initEye(btnId, inputId) {
        $('#' + btnId).on('click', function () {
            const $inp = $('#' + inputId);
            const $icon = $(this).find('i');
            const isPass = $inp.attr('type') === 'password';

            $inp.attr('type', isPass ? 'text' : 'password');
            $icon.attr('class', isPass ? 'ri-eye-line' : 'ri-eye-off-line');
        });
    }

    initEye('eyePassword', 'fPassword');
    initEye('eyeConfirm', 'fConfirm');

    /* =========================
        PASSWORD STRENGTH
    ========================= */

    function measureStrength(pw) {
        if (!pw) return 0;

        let score = 0;
        if (pw.length >= 8) score++;
        if (pw.length >= 12) score++;
        if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;

        if (score <= 1) return 1;
        if (score <= 3) return 2;
        return 3;
    }

    /* =========================
        VALIDATION RULES
    ========================= */

    const emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function validateName($el) {
        const v = trim($el.val());
        if (!v) return setInvalid($el, 'err-name', 'Full name is required.'), false;
        if (v.length < 2) return setInvalid($el, 'err-name', 'Minimum 2 characters.'), false;
        setValid($el, 'err-name');
        return true;
    }

    function validateEmail($el) {
        const v = trim($el.val());
        if (!v) return setInvalid($el, 'err-email', 'Email is required.'), false;
        if (!emailRx.test(v)) return setInvalid($el, 'err-email', 'Invalid email.'), false;
        setValid($el, 'err-email');
        return true;
    }

    function validatePassword($el) {
        const v = $el.val();
        if (!v) return setInvalid($el, 'err-password', 'Password required.'), false;
        if (v.length < 8) return setInvalid($el, 'err-password', 'Min 8 characters.'), false;
        setValid($el, 'err-password');
        return true;
    }

    function validateConfirm($el) {
        const v = $el.val();
        const pass = $('#fPassword').val();

        if (!v) return setInvalid($el, 'err-confirm', 'Confirm password.'), false;
        if (v !== pass) return setInvalid($el, 'err-confirm', 'Passwords mismatch.'), false;

        setValid($el, 'err-confirm');
        return true;
    }

    /* =========================
        EVENTS
    ========================= */

    $('#fName').on('blur', function () {
        validateName($(this));
    }).on('focus', function () {
        clearState($(this), 'err-name');
    });

    $('#fEmail').on('blur', function () {
        validateEmail($(this));
    }).on('focus', function () {
        clearState($(this), 'err-email');
    });

    $('#fPassword').on('input blur', function () {
        const v = $(this).val();
        const level = measureStrength(v);

        $('#strengthWrap').toggleClass('visible', !!v);
        $('#strengthLabel')
            .text('Strength: ' + ['', 'Weak', 'Medium', 'Strong'][level])
            .attr('class', 'strength-label level-' + level);

        validatePassword($(this));
    });

    $('#fConfirm').on('blur', function () {
        validateConfirm($(this));
    });

    /* =========================
        SUBMIT
    ========================= */

    $('#regForm').on('submit', function (e) {
        e.preventDefault();

        let valid = true;

        valid &= validateName($('#fName'));
        valid &= validateEmail($('#fEmail'));
        valid &= validatePassword($('#fPassword'));
        valid &= validateConfirm($('#fConfirm'));

        if (!$('#fTerms').is(':checked')) {
            $('#err-terms').show();
            valid = false;
        }

        if (!valid) return;

        const $btn = $('#btnSubmit');
        $btn.prop('disabled', true).addClass('loading');

        $('#btnText').text('Creating account...');

        setTimeout(function () {
            $btn.removeClass('loading').addClass('success');
            $('#btnText').html('<i class="ri-check-line"></i> Success');
        }, 2000);
    });

});