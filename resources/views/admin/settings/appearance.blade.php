@extends('admin.layout.app', ['title' => 'Appearance Settings'])

@section('content')
    <form class="ajax_form settings-page fm-body" method="POST" enctype="multipart/form-data" action="{{ route('admin.settings.post') }}">

        @csrf

        {{-- ===================================================== --}}
        {{-- Theme --}}
        {{-- ===================================================== --}}

        <div class="settings-card mb-3">

            <div class="settings-card-head">

                <div>
                    <h5>Theme Settings</h5>
                    <p>Configure the default appearance of the application.</p>
                </div>

                <i class="ri-palette-line"></i>

            </div>

            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label">
                        Default Theme
                    </label>

                    <select name="default_theme"
                            class="form-control select2-infinity">

                        <option value="light"
                            {{ get_settings('default_theme')=='light'?'selected':'' }}>
                            Light
                        </option>

                        <option value="dark"
                            {{ get_settings('default_theme')=='dark'?'selected':'' }}>
                            Dark
                        </option>

                        <option value="system"
                            {{ get_settings('default_theme')=='system'?'selected':'' }}>
                            Follow System
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Default Accent Color
                    </label>

                    <input type="color"
                            class="form-control form-control-color"
                            name="theme_color"
                            value="{{ get_settings('theme_color') ?: '#4F46E5' }}">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Sidebar Theme
                    </label>

                    <select name="sidebar_theme"
                            class="form-control select2-infinity">

                        <option value="light"
                            {{ get_settings('sidebar_theme')=='light'?'selected':'' }}>
                            Light
                        </option>

                        <option value="dark"
                            {{ get_settings('sidebar_theme')=='dark'?'selected':'' }}>
                            Dark
                        </option>

                        <option value="auto"
                            {{ get_settings('sidebar_theme')=='auto'?'selected':'' }}>
                            Auto
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Navbar Theme
                    </label>

                    <select name="navbar_theme"
                            class="form-control select2-infinity">

                        <option value="light"
                            {{ get_settings('navbar_theme')=='light'?'selected':'' }}>
                            Light
                        </option>

                        <option value="dark"
                            {{ get_settings('navbar_theme')=='dark'?'selected':'' }}>
                            Dark
                        </option>

                        <option value="transparent"
                            {{ get_settings('navbar_theme')=='transparent'?'selected':'' }}>
                            Transparent
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Card Style
                    </label>

                    <select name="card_style"
                            class="form-control select2-infinity">

                        <option value="default"
                            {{ get_settings('card_style')=='default'?'selected':'' }}>
                            Default
                        </option>

                        <option value="bordered"
                            {{ get_settings('card_style')=='bordered'?'selected':'' }}>
                            Bordered
                        </option>

                        <option value="shadow"
                            {{ get_settings('card_style')=='shadow'?'selected':'' }}>
                            Shadow
                        </option>

                        <option value="glass"
                            {{ get_settings('card_style')=='glass'?'selected':'' }}>
                            Glass
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Icon Style
                    </label>

                    <select name="icon_style"
                            class="form-control select2-infinity">

                        <option value="outline"
                            {{ get_settings('icon_style')=='outline'?'selected':'' }}>
                            Outline
                        </option>

                        <option value="fill"
                            {{ get_settings('icon_style')=='fill'?'selected':'' }}>
                            Fill
                        </option>

                        <option value="duotone"
                            {{ get_settings('icon_style')=='duotone'?'selected':'' }}>
                            Duotone
                        </option>

                    </select>

                </div>

            </div>

        </div>

        {{-- ===================================================== --}}
        {{-- Layout --}}
        {{-- ===================================================== --}}

        <div class="settings-card mb-3">

            <div class="settings-card-head">

                <div>
                    <h5>Layout Settings</h5>
                    <p>Control the overall application layout.</p>
                </div>

                <i class="ri-layout-4-line"></i>

            </div>

            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label">
                        Layout Width
                    </label>

                    <select name="layout_width"
                            class="form-control select2-infinity">

                        <option value="fluid"
                            {{ get_settings('layout_width')=='fluid'?'selected':'' }}>
                            Fluid
                        </option>

                        <option value="boxed"
                            {{ get_settings('layout_width')=='boxed'?'selected':'' }}>
                            Boxed
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Sidebar Position
                    </label>

                    <select name="sidebar_position"
                            class="form-control select2-infinity">

                        <option value="fixed"
                            {{ get_settings('sidebar_position')=='fixed'?'selected':'' }}>
                            Fixed
                        </option>

                        <option value="static"
                            {{ get_settings('sidebar_position')=='static'?'selected':'' }}>
                            Static
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Header Position
                    </label>

                    <select name="header_position"
                            class="form-control select2-infinity">

                        <option value="fixed"
                            {{ get_settings('header_position')=='fixed'?'selected':'' }}>
                            Fixed
                        </option>

                        <option value="static"
                            {{ get_settings('header_position')=='static'?'selected':'' }}>
                            Static
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Content Width
                    </label>

                    <select name="content_width"
                            class="form-control select2-infinity">

                        <option value="container"
                            {{ get_settings('content_width')=='container'?'selected':'' }}>
                            Container
                        </option>

                        <option value="container-fluid"
                            {{ get_settings('content_width')=='container-fluid'?'selected':'' }}>
                            Full Width
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Sidebar Width
                    </label>

                    <input type="number"
                            class="form-control"
                            name="sidebar_width"
                            value="{{ get_settings('sidebar_width') ?: 260 }}">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Content Max Width
                    </label>

                    <input type="number"
                            class="form-control"
                            name="content_max_width"
                            value="{{ get_settings('content_max_width') ?: 1600 }}">

                </div>


                <div class="col-md-6">

                    <div class="form-check form-switch mt-4">

                        <input class="form-check-input"
                                type="checkbox"
                                name="rtl_layout"
                                value="1"
                                {{ get_settings('rtl_layout') ? 'checked' : '' }}>

                        <label class="form-check-label">
                            Enable RTL Layout
                        </label>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="form-check form-switch mt-4">

                        <input class=" form-check-input"
                                type="checkbox"
                                name="boxed_shadow"
                                value="1"
                                {{ get_settings('boxed_shadow') ? 'checked' : '' }}>

                        <label class="form-check-label">
                            Box Layout Shadow
                        </label>

                    </div>

                </div>

            </div>

        </div>

        {{-- ===================================================== --}}
        {{-- Typography --}}
        {{-- ===================================================== --}}

        <div class="settings-card mb-3">

            <div class="settings-card-head">

                <div>
                    <h5>Typography</h5>
                    <p>Configure fonts and spacing used throughout the application.</p>
                </div>

                <i class="ri-font-size-2"></i>

            </div>

            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label">
                        Font Family
                    </label>

                    <select name="font_family"
                            class="form-control select2-infinity">

                        <option value="Inter"
                            {{ get_settings('font_family')=='Inter'?'selected':'' }}>
                            Inter
                        </option>

                        <option value="Poppins"
                            {{ get_settings('font_family')=='Poppins'?'selected':'' }}>
                            Poppins
                        </option>

                        <option value="Roboto"
                            {{ get_settings('font_family')=='Roboto'?'selected':'' }}>
                            Roboto
                        </option>

                        <option value="Open Sans"
                            {{ get_settings('font_family')=='Open Sans'?'selected':'' }}>
                            Open Sans
                        </option>

                        <option value="Nunito"
                            {{ get_settings('font_family')=='Nunito'?'selected':'' }}>
                            Nunito
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Base Font Size (px)
                    </label>

                    <input type="number"
                            name="font_size"
                            class="form-control"
                            min="12"
                            max="24"
                            value="{{ get_settings('font_size') ?: 14 }}">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Line Height
                    </label>

                    <input type="number"
                            step="0.1"
                            min="1"
                            max="2"
                            name="line_height"
                            class="form-control"
                            value="{{ get_settings('line_height') ?: 1.5 }}">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Border Radius (px)
                    </label>

                    <input type="number"
                            name="border_radius"
                            class="form-control"
                            value="{{ get_settings('border_radius') ?: 8 }}">

                </div>

            </div>

        </div>

        {{-- ===================================================== --}}
        {{-- Sidebar --}}
        {{-- ===================================================== --}}

        <div class="settings-card mb-3">

            <div class="settings-card-head">

                <div>
                    <h5>Sidebar Settings</h5>
                    <p>Customize sidebar behaviour and navigation experience.</p>
                </div>

                <i class="ri-sidebar-fold-line"></i>

            </div>

            <div class="row g-4">

                <div class="col-md-6">

                    <div class="form-check form-switch">

                        <input class="form-check-input"
                                type="checkbox"
                                name="sidebar_collapsed"
                                value="1"
                                {{ get_settings('sidebar_collapsed') ? 'checked' : '' }}>

                        <label class="form-check-label">
                            Collapse Sidebar By Default
                        </label>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="form-check form-switch">

                        <input class="form-check-input"
                                type="checkbox"
                                name="remember_sidebar"
                                value="1"
                                {{ get_settings('remember_sidebar') ? 'checked' : '' }}>

                        <label class="form-check-label">
                            Remember Sidebar State
                        </label>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="form-check form-switch">

                        <input class="form-check-input"
                                type="checkbox"
                                name="sidebar_hover_expand"
                                value="1"
                                {{ get_settings('sidebar_hover_expand') ? 'checked' : '' }}>

                        <label class="form-check-label">
                            Expand Sidebar On Hover
                        </label>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="form-check form-switch">

                        <input class="form-check-input"
                                type="checkbox"
                                name="sidebar_icons_only"
                                value="1"
                                {{ get_settings('sidebar_icons_only') ? 'checked' : '' }}>

                        <label class="form-check-label">
                            Icons Only Mode
                        </label>

                    </div>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Sidebar Animation
                    </label>

                    <select name="sidebar_animation"
                            class="form-control select2-infinity">

                        <option value="slide"
                            {{ get_settings('sidebar_animation')=='slide'?'selected':'' }}>
                            Slide
                        </option>

                        <option value="fade"
                            {{ get_settings('sidebar_animation')=='fade'?'selected':'' }}>
                            Fade
                        </option>

                        <option value="none"
                            {{ get_settings('sidebar_animation')=='none'?'selected':'' }}>
                            None
                        </option>

                    </select>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Sidebar Icon Size
                    </label>

                    <input type="number"
                            name="sidebar_icon_size"
                            class="form-control"
                            value="{{ get_settings('sidebar_icon_size') ?: 18 }}">

                </div>

            </div>

        </div>

        {{-- ===================================================== --}}
        {{-- Header --}}
        {{-- ===================================================== --}}

        <div class="settings-card mb-3">

            <div class="settings-card-head">

                <div>
                    <h5>Header Settings</h5>
                    <p>Control top navigation behaviour.</p>
                </div>

                <i class="ri-layout-top-line"></i>

            </div>

            <div class="row g-4">

                <div class="col-md-6">

                    <div class="form-check form-switch">

                        <input class="form-check-input"
                                type="checkbox"
                                name="sticky_header"
                                value="1"
                                {{ get_settings('sticky_header') ? 'checked' : '' }}>

                        <label class="form-check-label">
                            Sticky Header
                        </label>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="form-check form-switch">

                        <input class="form-check-input"
                                type="checkbox"
                                name="header_shadow"
                                value="1"
                                {{ get_settings('header_shadow') ? 'checked' : '' }}>

                        <label class="form-check-label">
                            Header Shadow
                        </label>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="form-check form-switch">

                        <input class="form-check-input"
                                type="checkbox"
                                name="show_breadcrumb"
                                value="1"
                                {{ get_settings('show_breadcrumb') ? 'checked' : '' }}>

                        <label class="form-check-label">
                            Show Breadcrumb
                        </label>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="form-check form-switch">

                        <input class="form-check-input"
                                type="checkbox"
                                name="show_global_search"
                                value="1"
                                {{ get_settings('show_global_search') ? 'checked' : '' }}>

                        <label class="form-check-label">
                            Enable Global Search
                        </label>

                    </div>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Header Height (px)
                    </label>

                    <input type="number"
                            name="header_height"
                            class="form-control"
                            value="{{ get_settings('header_height') ?: 64 }}">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Search Placeholder
                    </label>

                    <input type="text"
                            name="search_placeholder"
                            class="form-control"
                            value="{{ get_settings('search_placeholder') }}"
                            placeholder="Search anything...">

                </div>

            </div>

        </div>

        {{-- Footer Preview --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <h6 class="mb-3">Footer Preview</h6>
            </div>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Footer Background</label>
                    <input type="color"
                            class="form-control form-control-color"
                            name="footer_background"
                            value="{{ old('footer_background', get_settings('footer_background', '#212529')) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Footer Text Color</label>
                    <input type="color"
                            class="form-control form-control-color"
                            name="footer_text_color"
                            value="{{ old('footer_text_color', get_settings('footer_text_color', '#ffffff')) }}">
                </div>

                <div class="col-12">
                    <label class="form-label">Footer Copyright Text</label>
                    <textarea class="form-control"
                                rows="3"
                                name="footer_text"
                                placeholder="Copyright © 2026 Your Company">{{ old('footer_text', get_settings('footer_text')) }}</textarea>
                </div>

            </div>
        </div>

        {{-- Login Page --}}
        <div class="settings-card mb-3">
            <div class="settings-card-head">
                <h6 class="mb-3">Login Page Appearance</h6>
            </div>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Background Type</label>

                    <select class="form-control select2-infinity"
                            name="login_background_type">

                        <option value="color" {{ old('login_background_type', get_settings('login_background_type')) == 'color' ? 'selected' : '' }}>
                            Solid Color
                        </option>

                        <option value="gradient" {{ old('login_background_type', get_settings('login_background_type')) == 'gradient' ? 'selected' : '' }}>
                            Gradient
                        </option>

                        <option value="image" {{ old('login_background_type', get_settings('login_background_type')) == 'image' ? 'selected' : '' }}>
                            Image
                        </option>

                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Background Color</label>

                    <input type="color"
                            class="form-control form-control-color"
                            name="login_background_color"
                            value="{{ old('login_background_color', get_settings('login_background_color', '#f8fafc')) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Login Background Image</label>

                    <input type="file"
                            name="login_background_image"
                            class="form-control dropify"
                            data-default-file="{{ get_settings('login_background_image') ? asset(get_settings('login_background_image')) : '' }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Login Card Style</label>

                    <select class="form-control select2-infinity"
                            name="login_card_style">

                        <option value="default">Default</option>
                        <option value="glass">Glass</option>
                        <option value="bordered">Bordered</option>
                        <option value="minimal">Minimal</option>

                    </select>
                </div>

            </div>
        </div>

        {{-- Buttons --}}
        <div class="settings-card">
            <div class="settings-card-head">
                <h6 class="mb-3">Button Style</h6>
            </div>

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Primary Button Radius</label>

                    <input type="number"
                            class="form-control"
                            name="button_radius"
                            value="{{ old('button_radius', get_settings('button_radius',8)) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Button Shadow</label>

                    <select class="form-control select2-infinity"
                            name="button_shadow">

                        <option value="none">None</option>
                        <option value="small">Small</option>
                        <option value="medium">Medium</option>
                        <option value="large">Large</option>

                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Button Animation</label>

                    <select class="form-control select2-infinity"
                            name="button_animation">

                        <option value="none">None</option>
                        <option value="scale">Scale</option>
                        <option value="lift">Lift</option>
                        <option value="glow">Glow</option>

                    </select>
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
$(function () {

    _componentSelect();
    _componentDropify();

    _ajaxFormHandler('.ajax_form');

});
</script>
@endpush
