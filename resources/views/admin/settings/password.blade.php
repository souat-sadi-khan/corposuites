@extends('admin.layout.app', ['title' => 'My Profile'])

@section('content')
    <div class="password-update">
        <div class="pu-shell">
            <div class="pu-card">
                <div class="pu-visual">
                    <div class="pu-icon">
                        <i class="ri-shield-keyhole-line"></i>
                    </div>

                    <h4>Update Password</h4>
                    <p>Choose a strong password to keep your admin account protected.</p>
                </div>

                <form class="ajax_form pu-form" method="POST" action="{{ route('admin.update.password') }}">
                    @csrf

                    <div class="pu-field">
                        <label for="oldPassword">Old Password</label>
                        <div class="pu-input">
                            <i class="ri-lock-line"></i>
                            <input name="old_password" placeholder="Current password" id="oldPassword" required type="password">
                            <button type="button" class="pu-toggle-password" aria-label="Show password">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pu-field">
                        <label for="newPassword">New Password</label>
                        <div class="pu-input">
                            <i class="ri-key-2-line"></i>
                            <input name="password" required placeholder="New password" id="newPassword" type="password" minlength="8">
                            <button type="button" class="pu-toggle-password" aria-label="Show password">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                        <small>Minimum 8 characters</small>
                    </div>

                    <div class="pu-field">
                        <label for="confirmPassword">Confirm New Password</label>
                        <div class="pu-input">
                            <i class="ri-checkbox-circle-line"></i>
                            <input required name="password_confirmation" placeholder="Confirm new password" id="confirmPassword" type="password" minlength="8">
                            <button type="button" class="pu-toggle-password" aria-label="Show password">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                        <small>Repeat the new password exactly</small>
                    </div>

                    <div class="pu-strength" aria-hidden="true">
                        <span></span>
                    </div>

                    <button type="submit" id="submit" class="pu-submit">
                        <i class="ri-save-3-line"></i>
                        Save Changes
                    </button>

                    <button type="button" id="submitting" disabled style="display:none;" class="pu-submit">
                        <span class="spinner-border spinner-border-sm"></span>
                        Saving
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            _ajaxFormHandler('.ajax_form');
        })

        $(function () {
            $('.pu-toggle-password').on('click', function () {
                const button = $(this);
                const input = button.closest('.pu-input').find('input');
                const icon = button.find('i');
                const isPassword = input.attr('type') === 'password';

                input.attr('type', isPassword ? 'text' : 'password');
                icon.toggleClass('ri-eye-line', !isPassword);
                icon.toggleClass('ri-eye-off-line', isPassword);
            });

            $('#newPassword').on('input', function () {
                const value = $(this).val();
                let width = '33%';

                if (value.length >= 12 && /[A-Z]/.test(value) && /\d/.test(value) && /[^A-Za-z0-9]/.test(value)) {
                    width = '100%';
                } else if (value.length >= 8) {
                    width = '66%';
                }

                $('.pu-strength span').css('width', width);
            });
        });
    </script>
@endpush
