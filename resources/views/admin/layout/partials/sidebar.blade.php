<aside class="sidebar" id="sidebar">
    <input type="hidden" id="sidebarCollapsed" value="false">

    <!-- Header -->
    <div class="sb-header">
        <div class="sb-logo">

            <!-- Full Logo -->
            <a href="{{ route('admin.dashboard') }}" id="fullLogo">
                <img src="{{ get_settings('system_logo') ? asset(get_settings('system_logo')) : asset('assets/system/images/logo.png') }}"
                    alt="Logo"
                    class="sb-logo-img">
            </a>

            <!-- Favicon -->
            <a href="{{ route('admin.dashboard') }}" id="miniLogo" style="display: none;">
                <img src="{{ get_settings('system_favicon') ? asset(get_settings('system_favicon')) : asset('assets/system/images/favicon.png') }}"
                    alt="Logo"
                    class="sb-logo-img">
            </a>

        </div>
    </div>

    <!-- Navigation -->
    <nav class="sb-nav" id="sidebarNav">

        <!-- Collapsed-mode scroll arrows (shown/hidden by theme.js only
             when the icon list overflows) — replaces the scrollbar so a
             compacted sidebar never shows one. -->
        <button type="button" class="sb-nav-arrow sb-nav-arrow-up" id="sbNavArrowUp" aria-label="Scroll menu up" tabindex="-1">
            <i class="ri-arrow-up-s-line"></i>
        </button>

        <!-- ── MAIN ── -->
        <div class="sb-label">Main</div>

        <!-- Dashboard — leaf -->
        <div class="nav-item-wrap" data-flyout="" data-flyout-title="Dashboard">
            <a href="{{ route('admin.dashboard') }}" class="nav-row {{ Request::is('admin/dashboard') ? 'is-active' : '' }}" data-page="Dashboard">
                <i class="ri-dashboard-line n-icon"></i>
                <span class="n-lbl">Dashboard</span>
            </a>
        </div>

        <!-- ============================================ -->
        <!-- DYNAMIC MODULES (cached)                    -->
        <!-- ============================================ -->
        @php
            $dynamicMenus = app(\App\Services\ModuleMenuService::class)->getCachedMenus();
        @endphp

        @if(!empty($dynamicMenus))
            <div class="sb-label">Modules</div>

            @foreach($dynamicMenus as $module)
                @php
                    // Filter children by permission (module-level, i.e.
                    // top-level items directly under this module — either a
                    // group header, always null/always shown here, or a
                    // leaf link like "Employees"/"HRM Settings"). A group
                    // whose own nested items are ALL individually
                    // inaccessible is collapsed away inside
                    // dynamic_submenu.blade.php's own recursive filtering,
                    // not here.
                    $accessibleChildren = array_filter($module['children'], function ($child) {
                        return !isset($child['permission']) || $child['permission'] === null
                            || auth()->guard('admin')->user()?->can($child['permission']);
                    });

                    if (empty($accessibleChildren)) {
                        continue;
                    }

                    $moduleId = 'mod-' . $module['id'];

                    $isModuleActive = \Illuminate\Support\Facades\Route::current()
                        && checkMenuActive($accessibleChildren);
                @endphp

                <div class="nav-item-wrap has-sub"
                    data-flyout-title="{{ $module['label'] }}"
                    data-flyout-html='
                        <div class="fp-title">{{ $module['label'] }}</div>

                        @foreach($accessibleChildren as $child)

                            @php
                                $childHref = "#";

                                if (!empty($child["route"]) && \Illuminate\Support\Facades\Route::has($child["route"])) {
                                    $childHref = route($child["route"]);
                                } elseif (!empty($child["url"])) {
                                    $childHref = $child["url"];
                                }

                                // Pre-filter grandchildren so an empty group
                                // (every grandchild permission-filtered away)
                                // never prints its own "fp-sub-label" header
                                // with nothing underneath it.
                                $visibleGrandchildren = !empty($child["children"])
                                    ? array_filter($child["children"], function ($grandchild) {
                                        return !isset($grandchild["permission"])
                                            || $grandchild["permission"] === null
                                            || auth()->guard('admin')->user()?->can($grandchild["permission"]);
                                    })
                                    : [];
                            @endphp

                            @if(empty($child["children"]))
                                <a href="{{ $childHref }}">
                                    <i class="{{ $child["icon"] ?? "ri-circle-line" }}"></i>
                                    {{ $child["label"] }}
                                </a>
                            @elseif(!empty($visibleGrandchildren))

                                <div class="fp-sub-label">{{ $child["label"] }}</div>

                                @foreach($visibleGrandchildren as $grandchild)

                                    @php
                                        $grandHref = "#";

                                        if (!empty($grandchild["route"]) && \Illuminate\Support\Facades\Route::has($grandchild["route"])) {
                                            $grandHref = route($grandchild["route"]);
                                        } elseif (!empty($grandchild["url"])) {
                                            $grandHref = $grandchild["url"];
                                        }
                                    @endphp

                                    <div class="fp-sub-link">
                                        <a href="{{ $grandHref }}">
                                            <i class="{{ $grandchild["icon"] ?? "ri-circle-line" }}"></i>
                                            {{ $grandchild["label"] }}
                                        </a>
                                    </div>

                                @endforeach

                            @endif

                        @endforeach
                    '>

                    <!-- Level 1 trigger -->
                    <div class="nav-row {{ $isModuleActive ? 'is-active is-open' : '' }}"
                        data-target="sub-{{ $moduleId }}">
                        <i class="{{ $module['icon'] ?? 'ri-box-3-line' }} n-icon"></i>
                        <span class="n-lbl">{{ $module['label'] }}</span>
                        <i class="ri-arrow-right-s-line n-arrow"></i>
                    </div>

                    <div class="nav-sub"
                        id="sub-{{ $moduleId }}"
                        style="display: {{ $isModuleActive ? 'block' : 'none' }}">

                        @include('admin.layout.partials.dynamic_submenu', [
                            'items' => $accessibleChildren,
                            'prefix' => $moduleId,
                            'depth' => 0
                        ])

                    </div>

                </div>

            @endforeach

        @endif

        <!-- ── SYSTEM ── -->
        <div class="sb-label">System</div>

        <!-- Administrator -->
        <div class="nav-item-wrap has-sub"
            data-flyout-title="Administrator"
            data-flyout-html='
                <div class="fp-title">Administration</div>
                <a href="{{ route('admin.roles.index') }}"><i class="ri-shield-user-line"></i>Roles & Permission</a>
                <a href="{{ route('admin.stuff.index') }}"><i class="ri-user-line"></i>Users</a>
                <a href="{{ route('admin.modules.index') }}"><i class="ri-puzzle-line"></i>Modules</a>
                <a href="{{ route('admin.module-menus.index') }}"><i class="ri-menu-line"></i>Menu</a>
                <a href="{{ route('admin.activity.logs') }}"><i class="ri-file-list-3-line"></i>Audit Logs</a>
                <a href="{{ route('admin.sitemap') }}"><i class="ri-map-2-line"></i>Site Map</a>
            '>

            <div class="nav-row {{ Request::is('admin/roles*') || Request::is('admin/stuff*') || Request::is('admin/menu*') || Request::is('admin/recycle-bin*') || Request::is('admin/module*') || Request::is('admin/module-menus') || Request::is('admin/activity-logs*') || Request::is('admin/sitemap*') ? 'is-active is-open' : '' }}"
                data-target="sub-administration">

                <i class="ri-settings-4-fill n-icon"></i>
                <span class="n-lbl">Administration</span>
                <i class="ri-arrow-right-s-line n-arrow"></i>

            </div>


            <div class="nav-sub"
                style="display: {{ Request::is('admin/roles*') || Request::is('admin/stuff*') || Request::is('admin/menu*') || Request::is('admin/recycle-bin*') || Request::is('admin/module*') || Request::is('admin/activity-logs*') || Request::is('admin/module-menus') || Request::is('admin/sitemap*') ? 'block' : 'none' }}"
                id="sub-administration">

                <div class="nav-item-wrap">
                    <a href="{{ route('admin.roles.index') }}" class="nav-row {{ Request::is('admin/roles*') ? 'is-active' : '' }}"
                    data-page="Roles & Permission">

                        <i class="ri-shield-user-line n-icon"></i>
                        <span class="n-lbl">Roles & Permission</span>

                    </a>
                </div>

                <div class="nav-item-wrap">
                    <a href="{{ route('admin.stuff.index') }}" class="nav-row {{ Request::is('admin/stuff*') ? 'is-active' : '' }}" data-page="Users">
                        <i class="ri-user-line n-icon"></i>
                        <span class="n-lbl">Users</span>
                    </a>
                </div>

                <div class="nav-item-wrap">
                    <a href="{{ route('admin.modules.index') }}"
                    class="nav-row {{ Request::is('admin/module') ? 'is-active' : '' }}"
                    data-page="Menu">

                        <i class="ri-puzzle-line n-icon"></i>
                        <span class="n-lbl">Modules</span>

                    </a>
                </div>

                <div class="nav-item-wrap">
                    <a href="{{ route('admin.module-menus.index') }}"
                    class="nav-row {{ Request::is('admin/module-menus*') ? 'is-active' : '' }}"
                    data-page="Menu">

                        <i class="ri-menu-line n-icon"></i>
                        <span class="n-lbl">Menu</span>

                    </a>
                </div>

                <div class="nav-item-wrap">
                    <a href="{{ route('admin.activity.logs') }}"
                    class="nav-row {{ Request::is('admin/activity-logs*') ? 'is-active' : '' }}"
                    data-page="Audit Logs">
                        <i class="ri-file-list-3-line n-icon"></i>
                        <span class="n-lbl">Audit Logs</span>

                    </a>
                </div>

                <div class="nav-item-wrap">
                    <a href="{{ route('admin.sitemap') }}"
                    class="nav-row {{ Request::is('admin/sitemap*') ? 'is-active' : '' }}"
                    data-page="Site Map">

                        <i class="ri-map-2-line n-icon"></i>
                        <span class="n-lbl">Site Map</span>

                    </a>
                </div>
            </div>
        </div>

        <!-- Settings -->
        @if(!session('impersonating'))
            <div class="nav-item-wrap has-sub" data-flyout-title="Settings" data-flyout-html='
                <div class="fp-title">Settings</div>

                <a href="{{ route('admin.settings') }}">
                    <i class="ri-settings-3-line"></i>
                    General
                </a>

                <a href="{{ route('admin.profile') }}">
                    <i class="ri-user-settings-line"></i>
                    Profile
                </a>

                <a href="{{ route('admin.languages.index') }}">
                    <i class="ri-translate-2"></i>
                    Languages
                </a>

                <a href="{{ route('admin.settings.company') }}">
                    <i class="ri-building-4-line"></i>
                    Company
                </a>

                <a href="{{ route('admin.settings.branding') }}">
                    <i class="ri-palette-line"></i>
                    Branding
                </a>

                <a href="{{ route('admin.email.providers.index') }}">
                    <i class="ri-mail-line"></i>
                    Email Providers
                </a>

                <a href="{{ route('admin.email.sender-identities.index') }}">
                    <i class="ri-send-ins-line"></i>
                    Email Sender
                </a>

                <a href="{{ route('admin.email.email-templates.index') }}">
                    <i class="ri-image-line"></i>
                    Email Templates
                </a>

                <a href="{{ route('admin.settings.appearance') }}">
                    <i class="ri-layout-4-line"></i>
                    Appearance
                </a>

                <a href="{{ route('admin.settings.localization') }}">
                    <i class="ri-global-line"></i>
                    Localization
                </a>
            '>
                <div class="nav-row {{ Request::is('admin/profile') || Request::is('admin/edit-password') || Request::is('admin/edit-profile') || Request::is('admin/settings') || Request::is('admin/languages') || Request::is('admin/company') || Request::is('admin/branding') || Request::is('admin/appearance') || Request::is('admin/email/providers*') || Request::is('admin/email/sender-identities') || Request::is('admin/localization') || Request::is('admin/email/email-templates') ? 'is-active is-open' : '' }}"
                    data-target="sub-settings">

                    <i class="ri-settings-3-line n-icon"></i>

                    <span class="n-lbl">
                        Settings
                    </span>

                    <i class="ri-arrow-right-s-line n-arrow"></i>

                </div>

                <div class="nav-sub"
                    style="display: {{ Request::is('admin/profile') || Request::is('admin/edit-profile') || Request::is('admin/edit-password') || Request::is('admin/settings') || Request::is('admin/languages') || Request::is('admin/company') || Request::is('admin/branding') || Request::is('admin/email/providers*') || Request::is('admin/email/sender-identities') || Request::is('admin/appearance') ? 'block' || Request::is('admin/localization') || Request::is('admin/email/email-templates') : 'none' }}"
                    id="sub-settings">

                    <!-- General -->
                    <div class="nav-item-wrap">

                        <a href="{{ route('admin.settings') }}"
                            class="nav-row {{ Request::is('admin/settings') ? 'is-active' : '' }}"
                            data-page="General">

                            <i class="ri-settings-3-line n-icon"></i>

                            <span class="n-lbl">
                                General
                            </span>
                        </a>
                    </div>

                    <!-- Profile -->
                    <div class="nav-item-wrap">

                        <a href="{{ route('admin.profile') }}"
                            class="nav-row {{ Request::is('admin/profile') || Request::is('admin/edit-profile') || Request::is('admin/edit-password') ? 'is-active' : '' }}"
                            data-page="Profile">

                            <i class="ri-user-settings-line n-icon"></i>

                            <span class="n-lbl">
                                Profile
                            </span>
                        </a>

                    </div>

                    <!-- Languages -->
                    <div class="nav-item-wrap">

                        <a href="{{ route('admin.languages.index') }}"
                            class="nav-row {{ Request::is('admin/languages') ? 'is-active' : '' }}"
                            data-page="Languages">

                            <i class="ri-translate-2 n-icon"></i>

                            <span class="n-lbl">
                                Languages
                            </span>
                        </a>
                    </div>

                    <!-- Email Settings -->
                    <div class="nav-item-wrap has-sub">

                        <div class="nav-row {{ Request::is('admin/email/providers*') || Request::is('admin/email/sender-identities*') || Request::is('admin/email/email-templates*') ? 'is-active is-open' : '' }}"
                            data-target="sub-email-settings">

                            <i class="ri-mail-settings-line n-icon"></i>

                            <span class="n-lbl">
                                Email Settings
                            </span>

                            <i class="ri-arrow-right-s-line n-arrow"></i>

                        </div>

                        <div class="nav-sub"
                            id="sub-email-settings"
                            style="display: {{ Request::is('admin/email/providers*') || Request::is('admin/email/sender-identities*') || Request::is('admin/email/email-templates*') ? 'block' : 'none' }}">

                            <!-- Email Providers -->
                            <div class="nav-item-wrap">

                                <a href="{{ route('admin.email.providers.index') }}"
                                    class="nav-row {{ Request::is('admin/email/providers*') ? 'is-active' : '' }}"
                                    data-page="Email Providers">

                                    <i class="ri-mail-line n-icon"></i>

                                    <span class="n-lbl">
                                        Email Providers
                                    </span>

                                </a>

                            </div>

                            <!-- Email Senders -->
                            <div class="nav-item-wrap">

                                <a href="{{ route('admin.email.sender-identities.index') }}"
                                    class="nav-row {{ Request::is('admin/email/sender-identities*') ? 'is-active' : '' }}"
                                    data-page="Email Senders">

                                    <i class="ri-send-ins-line n-icon"></i>

                                    <span class="n-lbl">
                                        Email Senders
                                    </span>

                                </a>

                            </div>

                            <!-- Email Templates -->
                            <div class="nav-item-wrap">

                                <a href="{{ route('admin.email.email-templates.index') }}"
                                    class="nav-row {{ Request::is('admin/email/email-templates*') ? 'is-active' : '' }}"
                                    data-page="Email Templates">

                                    <i class="ri-image-line n-icon"></i>

                                    <span class="n-lbl">
                                        Email Templates
                                    </span>

                                </a>

                            </div>

                        </div>

                    </div>

                    <!-- Company -->
                    <div class="nav-item-wrap">

                        <a href="{{ route('admin.settings.company') }}"
                            class="nav-row {{ Request::is('admin/company') ? 'is-active' : '' }}"
                            data-page="Company">

                            <i class="ri-building-4-line n-icon"></i>

                            <span class="n-lbl">
                                Company
                            </span>
                        </a>
                    </div>

                    <!-- Branding -->
                    <div class="nav-item-wrap">

                        <a href="{{ route('admin.settings.branding') }}" class="nav-row {{ Request::is('admin/branding') ? 'is-active' : '' }}"
                            data-page="Branding">

                            <i class="ri-palette-line n-icon"></i>

                            <span class="n-lbl">
                                Branding
                            </span>
                        </a>
                    </div>

                    <!-- Appearance -->
                    <div class="nav-item-wrap">

                        <a href="{{ route('admin.settings.appearance') }}" class="nav-row {{ Request::is('admin/appearance') ? 'is-active' : '' }}"
                            data-page="Appearance">

                            <i class="ri-layout-4-line n-icon"></i>

                            <span class="n-lbl">
                                Appearance
                            </span>

                        </a>

                    </div>

                    <!-- Localization -->
                    <div class="nav-item-wrap">

                        <a href="{{ route('admin.settings.localization') }}" class="nav-row {{ Request::is('admin/localization') ? 'is-active' : '' }}"
                            data-page="Localization">

                            <i class="ri-global-line n-icon"></i>

                            <span class="n-lbl">
                                Localization
                            </span>

                        </a>

                    </div>

                </div>

            </div>
        @endif

        <!-- Document Management -->
        <div class="nav-item-wrap" data-flyout="" data-flyout-title="Document Management">
            <a href="{{ route('admin.dms.index') }}" class="nav-row {{ Request::is('admin/dms*') ? 'is-active' : '' }}" data-page="Document Management">
                <i class="ri-folders-line n-icon"></i>
                <span class="n-lbl">Documents</span>
            </a>
        </div>

        <!-- Notifications -->
        <div class="nav-item-wrap" data-flyout="" data-flyout-title="Notifications">
            <a href="{{ route('admin.notifications') }}" class="nav-row {{ Request::is('admin/notifications-page') ? 'is-active' : '' }}" data-page="Notifications List">
                <i class="ri-notification-3-line n-icon"></i>
                <span class="n-lbl">Notifications</span>
            </a>
        </div>

        <!-- Integrations — leaf -->
        <div class="nav-item-wrap" data-flyout="" data-flyout-title="Release History">
            <a href="{{ route('admin.release.history') }}" class="nav-row {{ Request::is('admin/release-history') ? 'is-active' : '' }}" data-page="Release History">
                <i class="ri-plug-line n-icon"></i>
                <span class="n-lbl">Release History</span>
            </a>
        </div>

        <!-- Documentation — leaf -->
        <div class="nav-item-wrap" data-flyout="" data-flyout-title="Documentation">
            <a href="{{ route('admin.documentation.index') }}" class="nav-row {{ Request::routeIs('admin.documentation.index') ? 'is-active' : '' }}" data-page="Documentation">
                <i class="ri-book-open-line n-icon"></i>
                <span class="n-lbl">Documentation</span>
            </a>
        </div>

        <button type="button" class="sb-nav-arrow sb-nav-arrow-down" id="sbNavArrowDown" aria-label="Scroll menu down" tabindex="-1">
            <i class="ri-arrow-down-s-line"></i>
        </button>
    </nav>
</aside>
