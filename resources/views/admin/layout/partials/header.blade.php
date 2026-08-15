<header class="topbar">
    <button class="tb-hamburger" id="mobileMenuBtn">
        <i class="ri-menu-line"></i>
    </button>

    <div class="tb-breadcrumb d-none d-md-flex">
        <button class="sb-toggle-btn" id="desktopCollapseBtn" title="Toggle sidebar">
            <i class="ri-side-bar-line"></i>
        </button>
        <span class="bc-page" id="bcPage">{{ isset($title) ? $title : 'Dashboard' }}</span>
    </div>

    <div class="tb-search">
        <i class="ri-search-line"></i>
        <input type="text" id="globalSearchInput" placeholder="Search anything...">

        <span class="tb-search-kbd">
            <kbd>Alt</kbd>
            <kbd>K</kbd>
        </span>

        <div class="search-dropdown" id="searchDropdown"></div>
    </div>

    <div class="tb-actions">

        <!-- Optimize -->
        <button
            class="tb-btn optimize-btn"
            id="optimizeBtn"
            title="Optimize & Clear Cache">

            <i class="ri-refresh-line"></i>

        </button>

        <!-- Quick menus -->
        <div class="tb-btn qm-trigger" id="qmTrigger">
            <i class="ri-apps-2-line"></i>

            <div class="tb-dd qm-dd" id="qmDropdown">
                <div class="qm-head">
                    <span class="qm-head-title">
                        {{ t('header.quick_menus') }}
                    </span>
                    <button class="qm-edit-toggle" id="qmEditToggle" title="Edit shortcuts">
                        <i class="ri-pencil-line"></i>
                    </button>
                </div>

                <div class="qm-grid" id="qmGrid">

                </div>
            </div>
        </div>

        <!-- Inline editor popover (single reusable instance) -->
        <div class="qm-editor" id="qmEditor">
            <div class="qm-editor-arrow"></div>
            <div class="qm-editor-row">
                <label>Label</label>
                <input type="text" id="qmEditLabel" maxlength="20" placeholder="e.g. Projects">
            </div>
            <div class="qm-editor-row">
                <label>URL</label>
                <input type="text" id="qmEditUrl" placeholder="/projects or https://...">
            </div>
            <div class="qm-editor-actions">
                <button class="qm-btn qm-btn-ghost" id="qmEditCancel">Cancel</button>
                <button class="qm-btn qm-btn-primary" id="qmEditSave">Save</button>
            </div>
        </div>

        <!-- Notifications -->
        <div class="position-relative">
            <button class="tb-btn" id="notifBtn">
                <i class="ri-notification-3-line"></i>
                <span class="tb-dot" id="notifDot" style="display: none;"></span>
            </button>
            <div class="tb-dd" id="notifDd" style="width:300px; display: none;">
                <div class="dd-head">
                    {{ t('header.notification') }}
                </div>
                <div id="notifContainer"></div>
                <div class="dd-foot">
                    <a href="{{ route('admin.notifications') }}">
                        {{ t('header.view_all_notifications') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Language Switcher -->
        <div class="tb-btn lang-trigger" id="langTrigger">
            <i class="ri-translate-2"></i>

            <div class="tb-dd lang-dd" id="langDropdown">
                <div class="dd-head">
                    {{ t('header.language') }}
                </div>

                <div class="lang-list" id="langList">
                    @foreach($languages as $lang)
                        <button type="button"  class="lang-item {{ app()->getLocale() == $lang->code ? 'is-active' : '' }}" data-lang="{{ $lang->code }}">
                            <span class="lang-flag">🌐</span>

                            <span class="lang-text">
                                <strong>{{ $lang->native_name }}</strong>
                                <small>{{ $lang->name }}</small>
                            </span>

                            <i class="ri-check-line lang-check"></i>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Theme -->
        <button class="tb-btn" id="themeBtn" title="Toggle theme">
            <i class="ri-moon-line"></i>
        </button>

        <!-- Customizer -->
        <button class="tb-btn" id="customizerBtn" title="Customize appearance">
            <i class="ri-palette-line"></i>
        </button>

        <!-- Profile -->
        <div class="position-relative ms-1">
            <button class="tb-btn tb-avatar" id="profileBtn">
                {{ strtoupper(substr(Auth::guard('admin')->user()->name, 0, 2)) }}
            </button>
            <div class="tb-dd" id="profileDd" style="min-width:190px;">
                <div class="pf-info">
                    <div class="pf-av">{{ strtoupper(substr(Auth::guard('admin')->user()->name, 0, 2)) }}</div>
                    <div>
                        <div class="pf-name">{{ Auth::guard('admin')->user()->name }}</div>
                        <div class="pf-email">{{ Auth::guard('admin')->user()->email }}</div>
                    </div>
                </div>
                <a href="{{ route('admin.profile') }}" class="pf-link">
                    <i class="ri-user-line"></i>
                    {{ t('header.my_profile_menu') }}
                </a>
                <a href="{{ route('admin.settings') }}" class="pf-link">
                    <i class="ri-settings-3-line"></i>
                    {{ t('header.settings_menu') }}
                </a>
                <a href="{{ route('admin.edit.password') }}" class="pf-link">
                    <i class="ri-shield-keyhole-line"></i>
                    {{ t('header.security_menu') }}
                </a>
                <hr class="pf-div">

                <button data-url="{{ route('admin.logout') }}" id="logout" onclick="logout()" class="pf-link danger">
                    <i class="ri-logout-box-r-line"></i>
                    {{ t('header.signout_menu') }}
                </button>
            </div>
        </div>
    </div>
</header>
