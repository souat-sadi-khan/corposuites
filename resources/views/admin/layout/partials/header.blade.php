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

    @if($attendanceWidget)
        <!-- Attendance Widget — always-visible status chip, left side of header -->
        <div class="position-relative">
            <button type="button" class="aw-chip aw-chip-{{ $attendanceWidget['state'] }}" id="attendanceWidgetBtn" title="My Attendance">
                <span class="aw-chip-dot" id="awChipDot"></span>
                <span class="aw-chip-label" id="awChipLabel">{{ $attendanceWidget['label'] }}</span>
                @if($attendanceWidget['check_in'])
                    <span class="aw-chip-time" id="awChipTime">· {{ $attendanceWidget['check_in'] }}</span>
                @else
                    <span class="aw-chip-time" id="awChipTime"></span>
                @endif
            </button>

            <div class="tb-dd aw-dd" id="attendanceWidgetDd">
                <div class="aw-head">
                    <div class="aw-today">Today</div>
                    <div class="aw-date" id="awDate">{{ $attendanceWidget['date_label'] }}</div>
                </div>

                <div class="aw-body" id="awBody">
                    @include('admin.layout.partials.attendance-widget-body', ['w' => $attendanceWidget])
                </div>

                <div class="dd-foot">
                    <a href="{{ route('admin.attendance-portal.index') }}">My Attendance</a>
                </div>
            </div>
        </div>

        <script>
            window.attendanceWidgetRoutes = {
                status: '{{ route('admin.attendance-widget.status') }}',
                checkIn: '{{ route('admin.attendance-portal.check-in') }}',
                checkOut: '{{ route('admin.attendance-portal.check-out') }}'
            };
        </script>

        {{--
            Shared Check In / Check Out modal — rendered once, globally, right
            here (header.blade.php is included on every admin page) so BOTH the
            header widget's buttons AND the dedicated "My Attendance" page's
            buttons trigger this exact same modal/flow via
            window.awOpenPunchModal(url, actionLabel) in attendance-widget.js,
            rather than each having its own separate window.prompt()-based
            flow. Shows the real current location (an embedded OpenStreetMap
            iframe — no API key, no new JS mapping library, per this project's
            own "don't add a dependency for one screen" precedent) plus an
            optional note, before the punch is actually sent.
        --}}
        <div class="modal fade" id="awPunchModal" tabindex="-1" aria-hidden="true" aria-labelledby="awPunchModalTitle">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content aw-punch-modal">
                    <div class="modal-header">
                        <h6 class="modal-title" id="awPunchModalTitle"><i class="ri-login-circle-fill"></i> Check In</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="aw-punch-location">
                            <div class="aw-punch-loading" id="awPunchLoading">
                                <span class="spinner-border spinner-border-sm"></span> Getting your current location…
                            </div>
                            <div class="aw-punch-location-content d-none" id="awPunchLocationContent">
                                <div class="aw-punch-map-wrap">
                                    <iframe id="awPunchMapFrame" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                                </div>
                                <div class="aw-punch-coords">
                                    <i class="ri-map-pin-fill"></i>
                                    <span id="awPunchCoordsText"></span>
                                    <a href="#" target="_blank" rel="noopener" id="awPunchMapLink" class="aw-punch-map-link">
                                        Open in Maps <i class="ri-external-link-line"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="aw-punch-location-error d-none" id="awPunchLocationError">
                                <i class="ri-error-warning-line"></i>
                                <span id="awPunchLocationErrorText"></span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label small fw-semibold mb-1" for="awPunchNotes">
                                <i class="ri-sticky-note-line"></i> Note <span class="text-muted">(optional)</span>
                            </label>
                            <textarea class="form-control form-control-sm" id="awPunchNotes" rows="2" maxlength="1000" placeholder="e.g. Client visit, WFH, back from lunch..."></textarea>
                        </div>

                        <div class="small mt-2 aw-punch-message" id="awPunchMessage"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-nx-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-nx-primary btn-sm" id="awPunchConfirmBtn" disabled>
                            <i class="ri-checkbox-circle-line"></i> <span id="awPunchConfirmLabel">Confirm</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
