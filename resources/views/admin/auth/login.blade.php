@extends('admin.layout.auth', ['title' => 'Welcome to Admin Login'])

@section('content')
    <div class="cm-shell" id="pageWrapper">
        <div class="cm-brand">

            <div class="cm-grid"></div>

            <div class="cm-glow cm-glow-1"></div>
            <div class="cm-glow cm-glow-2"></div>
            <div class="cm-glow cm-glow-3"></div>

            <div class="shape-layer" aria-hidden="true">

                <div class="deco-ring deco-ring-1"></div>
                <div class="deco-ring deco-ring-2"></div>
                <div class="deco-ring deco-ring-3"></div>

                <div class="deco-card deco-card-1">
                    <div class="deco-card-inner">
                        <div class="deco-card-bar long"></div>
                        <div class="deco-card-bar short"></div>
                        <div class="deco-card-bar" style="width:70%"></div>
                    </div>
                </div>
                <div class="deco-card deco-card-2">
                    <div class="deco-card-inner">
                        <div class="deco-card-bar" style="width:80%"></div>
                        <div class="deco-card-bar short"></div>
                    </div>
                </div>
                <div class="deco-card deco-card-3">
                    <div class="deco-card-inner">
                        <div class="deco-card-bar long"></div>
                        <div class="deco-card-bar" style="width:45%"></div>
                    </div>
                </div>

                {{-- Floating dots --}}
                <div class="deco-dot deco-dot-1"></div>
                <div class="deco-dot deco-dot-2"></div>
                <div class="deco-dot deco-dot-3"></div>
                <div class="deco-dot deco-dot-4"></div>

                {{-- Spinning hex outline --}}
                <div class="deco-hex">
                    <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <polygon points="50,2 93,26 93,74 50,98 7,74 7,26" stroke="#4f52e8" stroke-width="3" fill="none"/>
                        <polygon points="50,14 82,32 82,68 50,86 18,68 18,32" stroke="#4f52e8" stroke-width="1.5" fill="none"/>
                    </svg>
                </div>

                {{-- SVG connecting lines --}}
                <svg class="deco-lines" viewBox="0 0 600 700" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <line x1="0" y1="180" x2="600" y2="420" stroke="#4f52e8" stroke-width="1"/>
                    <line x1="120" y1="0" x2="480" y2="700" stroke="#4f52e8" stroke-width=".8"/>
                    <circle cx="480" cy="420" r="5" stroke="#4f52e8" stroke-width="1.5"/>
                    <circle cx="120" cy="180" r="3.5" stroke="#4f52e8" stroke-width="1.5"/>
                </svg>

            </div>

            <div class="cm-brand-inner">

                <div class="cm-auth-logo">
                    <img src="{{ get_settings('system_logo') ? asset(get_settings('system_logo')) : asset('assets/system/images/logo.png') }}" alt="CorpoSuites Logo">
                </div>

                {{-- Heading --}}
                <h1 class="cm-heading">
                    Run Your Business Seamlessly. <br> From a Single Suite.
                </h1>

                <p class="cm-desc">
                    Connect your teams, streamline operations, and manage your entire business from one powerful platform.
                </p>

                {{-- Typewriter feature box --}}
                <div class="cm-typewriter-box">
                    <div class="cm-tw-icon" id="twIcon">
                        <i class="ri-customer-service-2-line"></i>
                    </div>
                    <div class="cm-tw-text">
                        <span id="twText"></span><span class="cm-cursor"></span>
                    </div>
                </div>

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
                    </div>
                </div>

            </div>
        </div>

        {{-- ══════════════════════════ RIGHT PANEL — LOGIN FORM ══════════════════════════ --}}
        <div class="cm-auth">
            <div class="cm-auth-card" id="regCard">

                <h2 class="cm-auth-title">Welcome Back</h2>
                <p class="cm-auth-sub">Sign in to your CorpoSuites account.</p>

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
                    </div>

                    <button type="submit" id="submit" class="btn cm-btn-submit w-100">
                        Sign In <i class="ri-arrow-right-line ms-1"></i>
                    </button>
                    <button type="button" id="submitting" disabled style="display: none;" class="btn cm-btn-submit w-100">
                        <span class="spinner-border spinner-border-sm ms-2"></span>
                    </button>

                </form>

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
        
        // ── Eye toggle ──
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('ri-eye-line');
            this.querySelector('i').classList.toggle('ri-eye-off-line');
        });

        // ── Typewriter feature cycler ──
        const features = [
            { text: 'Customer Relationship Management', icon: 'ri-customer-service-2-line' },
            { text: 'Human Resource Management', icon: 'ri-team-line' },
            { text: 'Enterprise Resource Planning', icon: 'ri-building-4-line' },
            { text: 'Inventory & Operations', icon: 'ri-archive-stack-line' },
            { text: 'Accounting & Financial Management', icon: 'ri-calculator-line' },
            { text: 'Business Intelligence & Analytics', icon: 'ri-bar-chart-box-line' },
            { text: 'Connected Business Workflows', icon: 'ri-flow-chart' },
            { text: 'Enterprise-Grade Security', icon: 'ri-shield-check-line' },
        ];

        let fi = 0,
            ci = 0,
            typing = true;
        const twText = document.getElementById('twText');
        const twIcon = document.getElementById('twIcon');

        function typeStep() {
            const feat = features[fi];
            if (typing) {
                if (ci <= feat.text.length) {
                    twText.textContent = feat.text.slice(0, ci++);
                    setTimeout(typeStep, ci === 1 ? 0 : 38);
                } else {
                    setTimeout(() => { typing = false;
                        ci = feat.text.length;
                        typeStep(); }, 2000);
                }
            } else {
                if (ci > 0) {
                    twText.textContent = feat.text.slice(0, --ci);
                    setTimeout(typeStep, 22);
                } else {
                    fi = (fi + 1) % features.length;
                    typing = true;
                    twIcon.innerHTML = `<i class="${features[fi].icon}"></i>`;
                    setTimeout(typeStep, 300);
                }
            }
        }

        setTimeout(typeStep, 800);
    </script>
@endpush
