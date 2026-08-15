<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="icon" href="{{ get_settings('system_favicon') ? asset(get_settings('system_favicon')) : asset('assets/system/images/favicon.png') }}">
    <meta name="system-logo" content="{{ get_settings('system_logo') ? asset(get_settings('system_logo')) : asset('assets/system/images/logo.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title : 'Installer' }} - {{ get_settings('system_name') ?? 'Admin Template' }}</title>

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/system/css/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/system/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/system/css/switchery.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/system/css/dropify.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/system/css/parsley.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/system/fonts/remixicon.css') }}">

    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('assets/system/fonts/fonts.css') }}">

    <!-- App CSS -->
    <link rel="stylesheet" href="{{ asset('assets/system/css/app.css') }}">

    <script>
        (function() {
            // 1. Apply Dark/Light Theme immediately
            var savedTheme = localStorage.getItem('nx-theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);

            // 2. Fetch Customizer Settings
            var czSettings = JSON.parse(localStorage.getItem('nx-customizer') || '{}');
            var root = document.documentElement.style;

            // Setup Default Fallbacks
            var sidebarConfig = czSettings.sidebar || 'midnight';
            var headerConfig = czSettings.header || 'default';
            var accentConfig = czSettings.accent || '#4f52e8';

            // Color Helpers (Vanilla JS versions)
            function hexToRgba(hex, alpha) {
                hex = String(hex).replace('#', '');
                if (hex.length === 3) hex = hex.split('').map(function(c) { return c + c; }).join('');
                var num = parseInt(hex, 16);
                return 'rgba(' + ((num >> 16) & 255) + ',' + ((num >> 8) & 255) + ',' + (num & 255) + ',' + alpha + ')';
            }
            function shade(hex, percent) {
                hex = String(hex).replace('#', '');
                if (hex.length === 3) hex = hex.split('').map(function(c) { return c + c; }).join('');
                var num = parseInt(hex, 16);
                var r = (num >> 16) & 255, g = (num >> 8) & 255, b = num & 255;
                function clamp(v) { return Math.min(255, Math.max(0, Math.round(v + (percent / 100) * 255))); }
                return '#' + [clamp(r), clamp(g), clamp(b)].map(function(v) { return v.toString(16).padStart(2, '0'); }).join('');
            }
            function isLightColor(hex) {
                hex = String(hex).replace('#', '');
                if (hex.length === 3) hex = hex.split('').map(function(c) { return c + c; }).join('');
                var num = parseInt(hex, 16);
                var r = ((num >> 16) & 255) / 255, g = ((num >> 8) & 255) / 255, b = (num & 255) / 255;
                function lin(c) { return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); }
                return (0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b)) > 0.5;
            }

            // Apply Accent
            root.setProperty('--accent', accentConfig);
            root.setProperty('--accent-s', hexToRgba(accentConfig, .10));
            root.setProperty('--accent-m', hexToRgba(accentConfig, .18));
            root.setProperty('--accent-gl', hexToRgba(accentConfig, .28));
            root.setProperty('--accent-grad-1', shade(accentConfig, 8));
            root.setProperty('--accent-grad-2', shade(accentConfig, -10));

            // Apply Sidebar Palette
            var SB_PRESETS = {
                midnight: { bg: '#0c0d12', light: false }, slate: { bg: '#1e2536', light: false },
                ocean: { bg: '#0a2540', light: false }, forest: { bg: '#0f2418', light: false },
                indigo: { bg: '#1e1b4b', light: false }, white: { bg: '#ffffff', light: true }
            };
            var sbTheme = sidebarConfig === 'custom' && czSettings.sidebarCustom
                ? { bg: czSettings.sidebarCustom, light: isLightColor(czSettings.sidebarCustom) }
                : (SB_PRESETS[sidebarConfig] || SB_PRESETS.midnight);

            var sbT = sbTheme.light
                ? { tx: 'rgba(15,17,23,.55)', hover: 'rgba(15,17,23,.05)', active: 'rgba(79,82,232,.10)', activeTx: 'var(--accent)', border: 'rgba(15,17,23,.08)', label: 'rgba(15,17,23,.30)', strong: '#0f1117', txHover: 'rgba(15,17,23,.80)', faint: 'rgba(15,17,23,.30)' }
                : { tx: 'rgba(255,255,255,.48)', hover: 'rgba(255,255,255,.06)', active: 'rgba(79,82,232,.20)', activeTx: '#a2a4ff', border: 'rgba(255,255,255,.07)', label: 'rgba(255,255,255,.22)', strong: '#ffffff', txHover: 'rgba(255,255,255,.80)', faint: 'rgba(255,255,255,.24)' };

            root.setProperty('--sb-bg', sbTheme.bg); root.setProperty('--sb-tx', sbT.tx); root.setProperty('--sb-hover', sbT.hover); root.setProperty('--sb-active', sbT.active); root.setProperty('--sb-active-tx', sbT.activeTx); root.setProperty('--sb-border', sbT.border); root.setProperty('--sb-label', sbT.label); root.setProperty('--sb-tx-strong', sbT.strong); root.setProperty('--sb-tx-hover', sbT.txHover); root.setProperty('--sb-tx-faint', sbT.faint);

            // Apply Header Palette
            if (headerConfig === 'custom' && czSettings.headerCustom) {
                var hLight = isLightColor(czSettings.headerCustom);
                var hbT = hLight ? { tx1: '#0f1117', tx2: '#5d6070', tx3: '#9496a8', border: 'rgba(15,17,23,.10)', hover: 'rgba(15,17,23,.06)' } : { tx1: '#ffffff', tx2: 'rgba(255,255,255,.70)', tx3: 'rgba(255,255,255,.45)', border: 'rgba(255,255,255,.14)', hover: 'rgba(255,255,255,.12)' };
                root.setProperty('--tb-bg', czSettings.headerCustom); root.setProperty('--tb-tx-1', hbT.tx1); root.setProperty('--tb-tx-2', hbT.tx2); root.setProperty('--tb-tx-3', hbT.tx3); root.setProperty('--tb-border', hbT.border); root.setProperty('--tb-hover', hbT.hover);
            } else if (headerConfig === 'dark') {
                root.setProperty('--tb-bg', '#0c0d12'); root.setProperty('--tb-tx-1', '#ffffff'); root.setProperty('--tb-tx-2', 'rgba(255,255,255,.70)'); root.setProperty('--tb-tx-3', 'rgba(255,255,255,.45)'); root.setProperty('--tb-border', 'rgba(255,255,255,.14)'); root.setProperty('--tb-hover', 'rgba(255,255,255,.12)');
            } else if (headerConfig === 'colored') {
                root.setProperty('--tb-bg', 'var(--accent)'); root.setProperty('--tb-tx-1', '#ffffff'); root.setProperty('--tb-tx-2', 'rgba(255,255,255,.70)'); root.setProperty('--tb-tx-3', 'rgba(255,255,255,.45)'); root.setProperty('--tb-border', 'rgba(255,255,255,.14)'); root.setProperty('--tb-hover', 'rgba(255,255,255,.12)');
            } else if (headerConfig === 'gradient') {
                root.setProperty('--tb-bg', 'linear-gradient(135deg,var(--accent-grad-1),var(--accent-grad-2))'); root.setProperty('--tb-tx-1', '#ffffff'); root.setProperty('--tb-tx-2', 'rgba(255,255,255,.70)'); root.setProperty('--tb-tx-3', 'rgba(255,255,255,.45)'); root.setProperty('--tb-border', 'rgba(255,255,255,.14)'); root.setProperty('--tb-hover', 'rgba(255,255,255,.12)');
            }
        })();
    </script>

    @stack('styles')
</head>

<body>
    <script>
        // Apply layout positions instantly to body
        (function() {
            var cz = JSON.parse(localStorage.getItem('nx-customizer') || '{}');
            if (cz.position === 'right') document.body.classList.add('layout-sb-right');
            if (cz.width === 'boxed') document.body.classList.add('layout-boxed');
        })();
    </script>
    <div class="page-shell">
        <!-- Sidebar Wrapper -->
        @include('admin.layout.partials.sidebar')

        <div class="sb-overlay" id="sbOverlay"></div>
        <div class="flyout-panel" id="flyoutPanel"></div>

        <div class="main-area" id="mainArea">
            @include('admin.layout.partials.header')

            <!-- Main Content Start -->
            <main class="page-content">

                @if(session('impersonating'))
                    <div class="impersonate-bar mb-3">
                        <div class="impersonate-bar__msg">
                            <i class="ri-user-follow-line"></i>
                            <span>
                                You're viewing as
                                <strong>{{ auth()->guard('admin')->user()->name }}</strong>
                            </span>
                        </div>

                        <a href="{{ route('admin.impersonate.stop') }}" class="impersonate-bar__btn">
                            <i class="ri-logout-box-r-line"></i>
                            Back to Admin
                        </a>
                    </div>
                @endif

                @yield('content')
            </main>

            @include('admin.layout.partials.footer')
        </div>
    </div>

    @if(isset($offcanvas))
        <div
            class="offcanvas offcanvas-end custom-offcanvas"
            tabindex="-1"
            id="sideForm"
            style="--offcanvas-width: {{ $offcanvas }}">

            <div id="offcanvas-loader" class="text-center py-5" style="display:none">
                <div class="spinner-border"></div>
            </div>

            <div class="offcanvas-body p-0">

                <div class="offcanvas-content"></div>

            </div>

        </div>
    @endif

    @if(isset($modal))
        <div class="modal fade" id="modal_remote" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-{{ $modal }}">
                <div class="modal-content fm-modal-content">

                </div>
            </div>
        </div>
    @endif

    <div class="cz-overlay" id="czOverlay"></div>

    <aside class="cz-panel" id="czPanel">
        <div class="cz-head">
            <div>
                <h3>Customize</h3>
                <span>Live preview, saved automatically</span>
            </div>
            <button class="cz-close" id="czCloseBtn">
                <i class="ri-close-line"></i>
            </button>
        </div>

        <div class="cz-body">

            <!-- Mode -->
            <div class="cz-section">
                <div class="cz-section-title"><i class="ri-contrast-2-line"></i>Mode</div>
                <div class="cz-pill-row" id="czModeRow">
                    <div class="cz-pill" data-mode="light"><i class="ri-sun-line"></i>Light</div>
                    <div class="cz-pill" data-mode="dark"><i class="ri-moon-line"></i>Dark</div>
                </div>
            </div>

            <!-- Sidebar color -->
            <div class="cz-section">
                <div class="cz-section-title"><i class="ri-layout-left-line"></i>Sidebar Color</div>
                <div class="cz-swatch-grid" id="czSidebarSwatches">
                    <div class="cz-swatch" data-sb="midnight" style="background:#0c0d12" title="Midnight"></div>
                    <div class="cz-swatch" data-sb="slate"    style="background:#1e2536" title="Slate"></div>
                    <div class="cz-swatch" data-sb="ocean"    style="background:#0a2540" title="Ocean"></div>
                    <div class="cz-swatch" data-sb="forest"   style="background:#0f2418" title="Forest"></div>
                    <div class="cz-swatch" data-sb="indigo"   style="background:#1e1b4b" title="Indigo"></div>
                    <div class="cz-swatch cz-swatch-light" data-sb="white" style="background:#ffffff;border:1px solid var(--border)" title="White"></div>
                </div>
                <div class="cz-custom-row">
                    <label>Custom color</label>
                    <input type="color" class="cz-color-input" id="czSidebarCustom" value="#0c0d12">
                </div>
            </div>

            <!-- Header color -->
            <div class="cz-section">
                <div class="cz-section-title"><i class="ri-layout-top-line"></i>Header Color</div>
                <div class="cz-swatch-grid" id="czHeaderSwatches">
                    <div class="cz-swatch cz-swatch-light" data-tb="default" style="background:#ffffff;border:1px solid var(--border)" title="Default"></div>
                    <div class="cz-swatch" data-tb="dark" style="background:#0c0d12" title="Dark"></div>
                    <div class="cz-swatch" data-tb="colored" style="background:var(--accent)" title="Colored"></div>
                    <div class="cz-swatch" data-tb="gradient" style="background:linear-gradient(135deg,var(--accent-grad-1),var(--accent-grad-2))" title="Gradient"></div>
                </div>
                <div class="cz-custom-row">
                    <label>Custom color</label>
                    <input type="color" class="cz-color-input" id="czHeaderCustom" value="#ffffff">
                </div>
            </div>

            <!-- Accent color -->
            <div class="cz-section">
                <div class="cz-section-title"><i class="ri-drop-line"></i>Accent Color</div>
                <div class="cz-swatch-grid" id="czAccentSwatches">
                    <div class="cz-swatch" data-accent="#4f52e8" style="background:#4f52e8" title="Indigo"></div>
                    <div class="cz-swatch" data-accent="#0284c7" style="background:#0284c7" title="Blue"></div>
                    <div class="cz-swatch" data-accent="#16a34a" style="background:#16a34a" title="Green"></div>
                    <div class="cz-swatch" data-accent="#e11d48" style="background:#e11d48" title="Rose"></div>
                    <div class="cz-swatch" data-accent="#d97706" style="background:#d97706" title="Amber"></div>
                    <div class="cz-swatch" data-accent="#9333ea" style="background:#9333ea" title="Purple"></div>
                </div>
                <div class="cz-custom-row">
                    <label>Custom color</label>
                    <input type="color" class="cz-color-input" id="czAccentCustom" value="#4f52e8">
                </div>
            </div>

            <!-- Layout -->
            <div class="cz-section">
                <div class="cz-section-title"><i class="ri-layout-4-line"></i>Sidebar Position</div>
                <div class="cz-pill-row" id="czPosRow">
                    <div class="cz-pill" data-pos="left"><i class="ri-side-bar-line"></i>Left</div>
                    <div class="cz-pill" data-pos="right"><i class="ri-side-bar-fill"></i>Right</div>
                </div>
            </div>

            <div class="cz-section">
                <div class="cz-section-title"><i class="ri-layout-column-line"></i>Content Width</div>
                <div class="cz-pill-row" id="czWidthRow">
                    <div class="cz-pill" data-width="fluid"><i class="ri-expand-width-line"></i>Fluid</div>
                    <div class="cz-pill" data-width="boxed"><i class="ri-crop-line"></i>Boxed</div>
                </div>
            </div>
        </div>

        <div class="cz-foot">
            <button class="cz-reset-btn" id="czResetBtn"><i class="ri-restart-line"></i>Reset to Defaults</button>
        </div>
    </aside>

    <script>
        // Collapse sidebar instantly before user sees it
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.getElementById('sidebarCollapsed').value = true;
            document.getElementById('sidebar').classList.add('sidebar-collapsed');
            document.getElementById('mainArea').classList.add('is-collapsed');

            // Adjust logos instantly if they exist
            var fl = document.getElementById('fullLogo');
            var ml = document.getElementById('miniLogo');
            if(fl) fl.style.display = 'none';
            if(ml) ml.style.display = 'inline-flex';
        }
    </script>

    <script src="{{ asset('assets/system/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/system/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/system/js/switchery.min.js') }}"></script>
    <script src="{{ asset('assets/system/js/dropify.min.js') }}"></script>
    <script src="{{ asset('assets/system/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/system/js/parsley.min.js') }}"></script>
    <script src="{{ asset('assets/system/js/alert.js') }}"></script>
    <script src="{{ asset('assets/system/js/theme.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

	<script src="{{ asset('assets/system/js/main.js') }}"></script>
	<script src="{{ asset('assets/system/js/notification.js') }}"></script>

    @stack('scripts')
</body>
</html>
