@extends('admin.layout.app', ['title' => t('site_title.changelog')])

@section('content')

    <div class="nx-card tl-card">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Release history</div>
                    <div class="nx-card-sub">All notable changes to Nexus</div>
                </div>
                <div style="display:flex;gap:6px;font-size:11px;color:var(--tx-3);">
                    <span><i class="ri-calendar-line"></i> 7 releases</span>
                </div>
            </div>

            <div class="nx-card-body" style="padding-bottom:10px;">

            <!-- ============================================
            VERSION 1.3.2
            ============================================ -->
            <div class="cl-version">
                <span class="cl-tag">v1.3.2</span>
                <span class="cl-date">July 23, 2026</span>
                <span class="cl-badge" style="background:var(--green-s);color:var(--green);">feature</span>
            </div>

            <div class="cl-entry">
                <ul>

                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Email Template management system with support for creating, editing and managing reusable email templates.
                        </span>
                    </li>

                </ul>
            </div>

            <!-- ============================================
            VERSION 1.2.2
            ============================================ -->
            <div class="cl-version">
                <span class="cl-tag">v1.2.2</span>
                <span class="cl-date">July 21, 2026</span>
                <span class="cl-badge" style="background:var(--green-s);color:var(--green);">feature</span>
            </div>

            <div class="cl-entry">
                <ul>

                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Email Provider management system from Settings with support for configuring different mail service providers.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Email Sender management system for managing sender identities and email configuration.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot added">
                            <i class="ri-settings-3-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Enhanced Settings module with General, Branding, Appearance and Localization configuration sections.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot improved">
                            <i class="ri-settings-4-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Improved</strong> — Improved system configuration management with centralized settings control and better administration experience.
                        </span>
                    </li>

                </ul>
            </div>

            <div class="cl-divider"></div>


            <!-- ============================================
            VERSION 1.1.2
            ============================================ -->
            <div class="cl-version">
                <span class="cl-tag">v1.1.2</span>
                <span class="cl-date">July 20, 2026</span>
                <span class="cl-badge" style="background:var(--orange-s);color:var(--orange);">bug fix</span>
            </div>

            <div class="cl-entry">
                <ul>

                    <li>
                        <span class="cl-dot fixed">
                            <i class="ri-sun-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Fixed</strong> — Resolved dark and light theme switching issues with improved theme state synchronization.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot fixed">
                            <i class="ri-layout-left-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Fixed</strong> — Fixed sidebar collapse behavior and improved sidebar state persistence.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot improved">
                            <i class="ri-menu-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Improved</strong> — Optimized sidebar menu rendering performance and improved navigation experience.
                        </span>
                    </li>


                </ul>
            </div>

            <div class="cl-divider"></div>

            <!-- ============================================
            VERSION 1.1.1
            ============================================ -->
            <div class="cl-version">
                <span class="cl-tag">v1.1.1</span>
                <span class="cl-date">July 19, 2026</span>
                <span class="cl-badge" style="background:var(--orange-s);color:var(--orange);">bug fix</span>
            </div>

            <div class="cl-entry">
                <ul>

                    <li>
                        <span class="cl-dot fixed">
                            <i class="ri-tools-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Fixed</strong> — Resolved dashboard static content rendering issues and improved dynamic data loading behavior.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot improved">
                            <i class="ri-sun-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Improved</strong> — Added smooth animation transitions for dark and light theme switching experience.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot fixed">
                            <i class="ri-layout-left-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Fixed</strong> — Sidebar collapse state now persists after page refresh using saved user preferences.
                        </span>
                    </li>


                </ul>
            </div>


            <div class="cl-divider"></div>

            <!-- ============================================
            VERSION 1.1.0
            ============================================ -->
            <div class="cl-version">
                <span class="cl-tag">v1.1.0</span>
                <span class="cl-date">July 19, 2026</span>
                <span class="cl-badge" style="background:var(--green-s);color:var(--green);">feature</span>
            </div>

            <div class="cl-entry">
                <ul>

                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Dynamic interactive dashboard with real-time statistics, widgets and improved admin overview experience.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — User Management module with administrator listing, profile management, status control and account operations.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — System Sitemap module for managing and viewing registered application routes and navigation structure.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Audit Log module for tracking administrator actions, system activities and security-related events.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Settings management system including General Settings and Branding configuration modules.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot security">
                            <i class="ri-user-shared-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Administrator impersonation login feature for secure user account access and troubleshooting.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Dynamic Module Management system for installing, controlling and managing application modules.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Dynamic Module Menu Management with hierarchical menu structure, permissions and ordering support.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — System Optimizer button for clearing cache, configuration cache, route cache and view cache from admin panel.
                        </span>
                    </li>


                    <li>
                        <span class="cl-dot security">
                            <i class="ri-shield-check-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Improved</strong> — Enhanced administrative control with better security, management tools and system monitoring capabilities.
                        </span>
                    </li>

                </ul>
            </div>

            <div class="cl-divider"></div>

            <!-- ============================================
            VERSION 1.0.0
            ============================================ -->
            <div class="cl-version">
                <span class="cl-tag">v1.0.0</span>
                <span class="cl-date">July 14, 2026</span>
                <span class="cl-badge" style="background:var(--green-s);color:var(--green);">initial</span>
            </div>

            <div class="cl-entry">
                <ul>

                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Initial admin dashboard with secure authentication, login and logout functionality.
                        </span>
                    </li>

                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Complete Language Management module with CRUD, default language support, status management and language switching.
                        </span>
                    </li>

                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Translation Management module with automatic translation key scanning, editable translations and multilingual support.
                        </span>
                    </li>

                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Role Management powered by Spatie Permission including role assignment and status management.
                        </span>
                    </li>

                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Administrator (Staff) Management with profile management and password update functionality.
                        </span>
                    </li>

                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Global search system for quick navigation across administrative modules.
                        </span>
                    </li>

                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Real-time notification center with live updates, read status and bulk notification management.
                        </span>
                    </li>

                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Activity Log module for monitoring system events and administrator activities.
                        </span>
                    </li>

                    <li>
                        <span class="cl-dot added">
                            <i class="ri-add-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Added</strong> — Account settings including profile update, password change and release history page.
                        </span>
                    </li>

                    <li>
                        <span class="cl-dot security">
                            <i class="ri-shield-check-line"></i>
                        </span>
                        <span class="cl-msg">
                            <strong>Security</strong> — Protected all administrative modules using authentication middleware and role-based access control.
                        </span>
                    </li>

                </ul>
            </div>

            <div class="cl-divider"></div>

                <!-- divider after last is hidden via CSS -->
                <div class="cl-divider"></div>

                <!-- subtle "end" note -->
                <div style="text-align:center;padding:16px 0 4px;font-size:11px;color:var(--tx-3);">
                    <i class="ri-check-double-line"></i> No more releases — you're all caught up.
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')

@endpush
