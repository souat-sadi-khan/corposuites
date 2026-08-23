@extends('admin.layout.app', ['title' => 'Branding Settings'])

@section('content')

    <form class="ajax_form settings-page" method="POST" enctype="multipart/form-data" action="{{ route('admin.settings.post') }}">
        @csrf

        {{-- Brand Assets --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Brand Assets</h5>
                    <p>Manage logos, icons and visual identity assets.</p>
                </div>

                <i class="ri-image-line"></i>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        Main Logo
                    </label>

                    <input type="file" name="system_logo" class="form-control dropify" data-default-file="{{ get_settings('system_logo') ? asset(get_settings('system_logo')) : '' }}">

                    <small class="text-muted">
                        Recommended size: 200x60px
                    </small>
                </div>


                <div class="col-md-6">
                    <label class="form-label">
                        Dark Logo
                    </label>

                    <input type="file" name="brand_dark_logo" class="form-control dropify" data-default-file="{{ get_settings('brand_dark_logo') ? asset(get_settings('brand_dark_logo')) : '' }}">

                    <small class="text-muted">
                        Used on dark background layouts.
                    </small>

                </div>


                <div class="col-md-6">
                    <label class="form-label">
                        Favicon
                    </label>

                    <input type="file" name="system_favicon" class="form-control dropify" data-default-file="{{ get_settings('system_favicon') ? asset(get_settings('system_favicon')) : '' }}">

                </div>


                <div class="col-md-6">
                    <label class="form-label">
                        Application Icon
                    </label>

                    <input type="file" name="app_icon" class="form-control dropify" data-default-file="{{ get_settings('app_icon') ? asset(get_settings('app_icon')) : '' }}">
                </div>
            </div>
        </div>

        {{-- Brand Identity --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Brand Identity</h5>
                    <p>Configure official brand information.</p>
                </div>
                <i class="ri-building-line"></i>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        Brand Name
                    </label>

                    <input type="text" name="brand_name" class="form-control" value="{{ get_settings('brand_name') }}" placeholder="Enter brand name">
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Brand Short Name
                    </label>

                    <input type="text"
                            name="brand_short_name"
                            class="form-control"
                            value="{{ get_settings('brand_short_name') }}"
                            placeholder="Short name">

                </div>

                <div class="col-md-6">

                    <label class="form-label">
                        Brand Tagline
                    </label>

                    <input type="text"
                            name="brand_tagline"
                            class="form-control"
                            value="{{ get_settings('brand_tagline') }}"
                            placeholder="Your brand tagline">

                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Brand Website
                    </label>

                    <input type="url" name="brand_website" class="form-control" value="{{ get_settings('brand_website') }}" placeholder="https://example.com">
                </div>

                <div class="col-md-12">
                    <label class="form-label">
                        Brand Description
                    </label>

                    <textarea name="brand_description" class="form-control" rows="3" placeholder="Short brand description">{{ get_settings('brand_description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Theme Colors --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Brand Colors</h5>
                    <p>Customize primary colors used throughout the system.</p>
                </div>

                <i class="ri-palette-line"></i>
            </div>

            <div class="row g-3">
                <div class="col-md-4">

                    <label class="form-label">
                        Primary Color
                    </label>

                    <input type="color"
                            name="primary_color"
                            class="form-control form-control-color"
                            value="{{ get_settings('primary_color') ?? '#2563eb' }}">

                </div>

                <div class="col-md-4">

                    <label class="form-label">
                        Secondary Color
                    </label>

                    <input type="color"
                            name="secondary_color"
                            class="form-control form-control-color"
                            value="{{ get_settings('secondary_color') ?? '#64748b' }}">

                </div>

                <div class="col-md-4">

                    <label class="form-label">
                        Accent Color
                    </label>

                    <input type="color"
                            name="accent_color"
                            class="form-control form-control-color"
                            value="{{ get_settings('accent_color') ?? '#0ea5e9' }}">

                </div>
            </div>
        </div>

        {{-- Email Branding --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Email Branding</h5>
                    <p>Brand identity used in outgoing emails.</p>
                </div>

                <i class="ri-mail-line"></i>
            </div>

            <div class="row g-3">
                <div class="col-md-12">

                    <label class="form-label">
                        Email Logo
                    </label>

                    <input type="file"
                            name="email_logo"
                            class="form-control dropify"
                            data-default-file="{{ get_settings('email_logo') ? asset(get_settings('email_logo')) : '' }}">
                </div>

                <div class="col-md-12">

                    <label class="form-label">
                        Email Footer Text
                    </label>

                    <input type="text"
                            name="email_footer_text"
                            class="form-control"
                            value="{{ get_settings('email_footer_text') }}"
                            placeholder="Copyright text">

                </div>
            </div>
        </div>

        {{-- Printable Branding --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Printable Branding</h5>
                    <p>Brand identity used in printable media.</p>
                </div>

                <i class="ri-mail-line"></i>
            </div>

            <div class="row g-3">
                <div class="col-md-12">

                    <label class="form-label">
                        Print Logo
                    </label>

                    <input type="file"
                            name="printable_logo"
                            class="form-control dropify"
                            data-default-file="{{ get_settings('printable_logo') ? asset(get_settings('printable_logo')) : '' }}">
                </div>
            </div>
        </div>

        {{-- Social Branding --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <div>
                    <h5>Social Branding</h5>
                    <p>Manage public social media links.</p>
                </div>

                <i class="ri-share-line"></i>

            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        Facebook
                    </label>

                    <input type="url"
                            name="facebook_url"
                            class="form-control"
                            value="{{ get_settings('facebook_url') }}">

                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        LinkedIn
                    </label>

                    <input type="url"
                            name="linkedin_url"
                            class="form-control"
                            value="{{ get_settings('linkedin_url') }}">

                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Instagram
                    </label>

                    <input type="url"
                            name="instagram_url"
                            class="form-control"
                            value="{{ get_settings('instagram_url') }}">

                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        YouTube
                    </label>

                    <input type="url"
                            name="youtube_url"
                            class="form-control"
                            value="{{ get_settings('youtube_url') }}">

                </div>
            </div>
        </div>

        <div class="settings-save-box mt-3">
            <button type="submit" id="submit" class="settings-submit">
                <i class="ri-save-3-line"></i>
                Update Settings
            </button>
            <button type="button" id="submitting" disabled style="display: none;" class="settings-submit">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        $(document).ready(function(){
            _componentDropify();
            _ajaxFormHandler('.ajax_form');
        });
    </script>
@endpush
