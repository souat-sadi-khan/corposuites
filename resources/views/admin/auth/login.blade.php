@extends('admin.layout.auth', ['title' => 'Welcome to Admin Login'])

@section('content')
<style>
    :root{
        --cm-navy-1:#0B1220;
        --cm-navy-2:#111827;
        --cm-navy-3:#0F172A;
        --cm-accent:#4f52e8;
        --cm-accent-2:#6567f5;
        --cm-accent-soft:rgba(79,82,232,.14);
        --cm-txt-1:#0f1117;
        --cm-txt-2:#5d6070;
        --cm-txt-3:#9496a8;
        --cm-border:#e2e5ea;
    }
</style>

<div class="cm-shell" id="pageWrapper">

    {{-- ══════════════════════════ LEFT PANEL — BRAND ══════════════════════════ --}}
    <div class="cm-brand">

        {{-- Decorative background layer --}}
        <div class="cm-brand-bg" aria-hidden="true">
            <div class="cm-grid"></div>
            <div class="cm-glow cm-glow-1"></div>
            <div class="cm-glow cm-glow-2"></div>
            <div class="cm-shape cm-shape-1"></div>
            <div class="cm-shape cm-shape-2"></div>
        </div>

        <div class="cm-brand-inner">

            {{-- Heading --}}
            <h1 class="cm-heading">
                Everything Your Business<br>
                Needs. In One Suite.
            </h1>

            <p class="cm-desc">
                Connect your teams, streamline your operations, and manage your entire business from one powerful enterprise platform.
            </p>

            {{-- Feature list --}}
            <ul class="cm-features" role="list">
                <li style="--d:0s"><span class="cm-feature-icon"><i class="ri-customer-service-2-line"></i></span>Customer Relationship Management</li>
                <li style="--d:.15s"><span class="cm-feature-icon"><i class="ri-team-line"></i></span>Human Resource Management</li>
                <li style="--d:.3s"><span class="cm-feature-icon"><i class="ri-building-4-line"></i></span>Enterprise Resource Planning</li>
                <li style="--d:.45s"><span class="cm-feature-icon"><i class="ri-archive-stack-line"></i></span>Inventory &amp; Operations</li>
                <li style="--d:.6s"><span class="cm-feature-icon"><i class="ri-calculator-line"></i></span>Accounting &amp; Financial Management</li>
                <li style="--d:.75s"><span class="cm-feature-icon"><i class="ri-bar-chart-box-line"></i></span>Business Intelligence &amp; Analytics</li>
                <li style="--d:.9s"><span class="cm-feature-icon"><i class="ri-flow-chart"></i></span>Connected Business Workflows</li>
                <li style="--d:1.05s"><span class="cm-feature-icon"><i class="ri-shield-check-line"></i></span>Enterprise-Grade Security</li>
            </ul>

            {{-- Trusted by / tech badges --}}
            <div class="cm-trusted">
                <span class="cm-trusted-label">Powering Modern Enterprises</span>
                <div class="cm-tech-badges">
                    <span class="cm-tech-badge"><i class="ri-user-3-line"></i> CRM</span>
                    <span class="cm-tech-badge"><i class="ri-team-line"></i> HRM</span>
                    <span class="cm-tech-badge"><i class="ri-building-line"></i> ERP</span>
                    <span class="cm-tech-badge"><i class="ri-archive-line"></i> Inventory</span>
                    <span class="cm-tech-badge"><i class="ri-wallet-3-line"></i> Accounting</span>
                    <span class="cm-tech-badge"><i class="ri-line-chart-line"></i> Analytics</span>
                    <span class="cm-tech-badge"><i class="ri-apps-2-line"></i> One Platform</span>
                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════ RIGHT PANEL — LOGIN FORM ══════════════════════════ --}}
    <div class="cm-auth">
        <div class="cm-auth-card" id="regCard">

            <div class="cm-auth-logo">
                <img src="{{ get_settings('system_logo') ? asset(get_settings('system_logo')) : asset('assets/system/images/logo.png') }}" alt="CorpoSuites Logo">
            </div>

            <h2 class="cm-auth-title">Welcome Back</h2>
            <p class="cm-auth-sub">Sign in to your CorpoSuites account.</p>

            {{-- ══ FORM (Blade / backend logic fully preserved) ══ --}}
            <form class="ajax_form" method="POST" action="{{ route('admin.login.post') }}">

                <div class="mb-3">
                    <label for="email" class="form-label cm-label">Email</label>
                    <div class="cm-input-wrap">
                        <i class="ri-mail-line cm-input-icon"></i>
                        <input type="email" class="form-control cm-input" name="email" id="email" placeholder="you@company.com" required>
                    </div>
                </div>

                <div class="mb-2">
                    <label for="password" class="form-label cm-label">Password</label>
                    <div class="cm-input-wrap">
                        <i class="ri-lock-2-line cm-input-icon"></i>
                        <input type="password" id="password" name="password" required class="form-control cm-input cm-input-pass" placeholder="Enter your password">
                        <button class="cm-eye-btn" type="button" id="togglePassword" tabindex="-1">
                            <i class="ri-eye-line fs-5 lh-0"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between cm-row-between">
                    <div class="form-check">
                        <input class="form-check-input cm-check" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label cm-remember-label" for="remember">Remember me</label>
                    </div>
                    {{-- <a href="#" class="cm-forgot-link">Forgot password?</a> --}}
                </div>

                <button type="submit" id="submit" class="btn cm-btn-submit w-100">
                    Sign In <i class="ri-arrow-right-line ms-1"></i>
                </button>
                <button type="button" id="submitting" disabled style="display: none;" class="btn cm-btn-submit w-100">
                    <span class="spinner-border spinner-border-sm ms-2"></span>
                </button>
            </form>

            {{-- Divider --}}
            {{-- <div class="cm-divider">OR</div> --}}

            {{-- Social (UI only, no functionality) --}}
            {{-- <div class="cm-social-row">
                <button type="button" class="cm-btn-social" disabled>
                    <i class="ri-google-fill" style="color:#EA4335"></i> Google
                </button>
                <button type="button" class="cm-btn-social" disabled>
                    <i class="ri-microsoft-fill" style="color:#00A4EF"></i> Microsoft
                </button>
            </div> --}}

            <div class="cm-secure-footer">
                <i class="ri-shield-check-fill"></i> Protected by enterprise-grade authentication.
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            _ajaxFormHandler('.ajax_form');
        });

        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('ri-eye-line');
            this.querySelector('i').classList.toggle('ri-eye-off-line');
        });
    </script>
@endpush
