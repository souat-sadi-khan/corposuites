@extends('admin.layout.app', ['title' => t('site_title.dashboard_title'), 'modal' => 'lg'])

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/system/css/dashboard.css') }}">
    <style>
    </style>
@endpush

@section('content')

    <div class="welcome-card">
        <div class="wc-left">
            <div class="wc-greet" id="wcGreet">
                Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }}, {{ $user->name }} 👋
            </div>

            <div class="wc-sub">
                Here's your workspace snapshot for today.
            </div>

            <div class="wc-meta">
                <div class="wc-meta-item">
                    Session start
                    <strong>{{ session('session_start') ? \Carbon\Carbon::parse(session('session_start'))->format('M d, Y h:i A') : 'Unavailable' }}</strong>
                </div>

                <div class="wc-meta-item">
                    IP address
                    <strong>{{ request()->ip() }}</strong>
                </div>

                <div class="wc-meta-item">
                    Language
                    <strong>{{ session('locale', 'English') }}</strong>
                </div>

                <div class="wc-meta-item">
                    Environment
                    <strong>{{ ucfirst(app()->environment()) }}</strong>
                </div>
            </div>
        </div>

        <div class="wc-right">
            <div class="wc-icon">
                <i class="ri-sun-cloudy-line"></i>
            </div>

            <div class="wc-clock" id="wcClock"></div>
            <div class="wc-date" id="wcDate"></div>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">{{ t('dashboard.total_staff') }}</div>
                <div class="stat-val">{{ number_format($totalStaff) }}</div>
            </div>
            <div class="stat-icon-wrap si-blue">
                <i class="ri-group-fill"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">{{ t('dashboard.active_staff') }}</div>
                <div class="stat-val">{{ number_format($activeStaff) }}</div>
            </div>
            <div class="stat-icon-wrap si-green">
                <i class="ri-user-follow-fill"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">{{ t('dashboard.inactive_staff') }}</div>
                <div class="stat-val">{{ number_format($inactiveStaff) }}</div>
            </div>
            <div class="stat-icon-wrap si-amber">
                <i class="ri-user-unfollow-fill"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-lbl">{{ t('dashboard.roles') }}</div>
                <div class="stat-val">{{ number_format($totalRoles) }}</div>
            </div>
            <div class="stat-icon-wrap si-purple">
                <i class="ri-shield-user-fill"></i>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="sec-hdr">
        <div>
            <h2>
                Quick Actions
            </h2>
            <div class="sec-sub">
                Jump straight into common tasks
            </div>
        </div>
    </div>

    <div class="qa-grid mb-3">

        <a href="{{ route('admin.stuff.index') }}" class="qa-card">
            <div class="qa-icon si-blue"><i class="ri-user-add-line"></i></div>
            <div class="qa-title">Manage User</div>
            <div class="qa-desc">Onboard a member</div>
        </a>

        <a href="{{ route('admin.roles.index') }}" class="qa-card">
            <div class="qa-icon si-purple"><i class="ri-shield-star-line"></i></div>
            <div class="qa-title">Manage Role</div>
            <div class="qa-desc">Define permissions</div>
        </a>

        <a href="{{ route('admin.languages.index') }}" class="qa-card">
            <div class="qa-icon si-green"><i class="ri-earth-line"></i></div>
            <div class="qa-title">Manage Languages</div>
            <div class="qa-desc">Locales & regions</div>
        </a>

        <a href="{{ route('admin.activity.logs') }}" class="qa-card">
            <div class="qa-icon si-blue"><i class="ri-file-list-3-line"></i></div>
            <div class="qa-title">Activity Logs</div>
            <div class="qa-desc">Audit trail</div>
        </a>

        <a href="{{ route('admin.profile') }}" class="qa-card">
            <div class="qa-icon si-purple"><i class="ri-user-settings-line"></i></div>
            <div class="qa-title">Profile</div>
            <div class="qa-desc">Your account</div>
        </a>

        <a href="{{ route('admin.settings') }}" class="qa-card">
            <div class="qa-icon si-green"><i class="ri-settings-3-line"></i></div>
            <div class="qa-title">Settings</div>
            <div class="qa-desc">System config</div>
        </a>
    </div>

    <!-- Activity & Login Overview -->
    <div class="sec-hdr">
        <div>
            <h2>Activity & Login Overview</h2>
            <div class="sec-sub">Last 30 days</div>
        </div>
    </div>

    <div class="twin-row">
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Last 30 Days Activity</div>
                    <div class="nx-card-sub">
                        Today:
                        <strong style="color:var(--tx-1)">{{ number_format($todayActivity) }} events</strong>
                        · Week
                        <span style="color:{{ $weekGrowth >= 0 ? 'var(--green)' : 'var(--red)' }}">
                            {{ $weekGrowth >= 0 ? '▲' : '▼' }} {{ abs($weekGrowth) }}%
                        </span>
                        · Month
                        <span style="color:{{ $monthGrowth >= 0 ? 'var(--green)' : 'var(--red)' }}">
                            {{ $monthGrowth >= 0 ? '▲' : '▼' }} {{ abs($monthGrowth) }}%
                        </span>
                    </div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="activityChart30"></canvas>
                </div>
            </div>
        </div>

        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">User Login Trend</div>
                    <div class="nx-card-sub">Successful logins, last 30 days</div>
                </div>
            </div>

            <div class="nx-card-body">
                <div class="chart-wrap">
                    <canvas id="loginChart30"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="bottom-row">

        <!-- Recent Activity -->
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Recent Activity</div>
                    <div class="nx-card-sub">Latest actions across the system</div>
                </div>

                <a href="{{ route('admin.activity.logs') }}" class="btn-nx-outline" style="font-size:12px;padding:5px 11px;">
                    View All <i class="ri-arrow-right-s-line"></i>
                </a>
            </div>

            <div style="overflow-x:auto;">
                <table class="ractivity-tbl">
                    <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                    </thead>

                    <tbody>

                    @foreach($activities as $activity)

                        @php
                            $name = $activity->admin->name ?? 'System';
                            $initial = strtoupper(substr($name,0,2));
                        @endphp

                        <tr>

                            <td>
                                <div class="ru-cell">
                                    <div class="ru-av" style="background:linear-gradient(135deg,#6567f5,#a855f7)">
                                        {{ $initial }}
                                    </div>

                                    {{ $name }}
                                </div>
                            </td>

                            <td>
                                {{ $activity->description }}
                            </td>

                            <td>
                                <span class="mod-chip">
                                    {{ ucfirst($activity->module) }}
                                </span>
                            </td>

                            <td style="color:var(--tx-3);font-size:12px;">
                                {{ $activity->created_at->diffForHumans() }}
                            </td>

                            <td>
                                <span class="badge-s bs-done">
                                    {{ ucfirst($activity->action) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Notification -->
        <div class="nx-card">
            <div class="nx-card-hdr">
                <div>
                    <div class="nx-card-title">Recent Notifications</div>
                    <div class="nx-card-sub">
                        {{ $unreadNotifications }} unread
                    </div>
                </div>
            </div>

            <div class="notif-list">

                @foreach($notifications as $notification)

                    <div class="notif-item">

                        <div class="notif-icon si-blue">
                            <i class="ri-notification-3-line"></i>
                        </div>

                        <div class="notif-body">

                            <div class="notif-title-row">

                                <span class="notif-title">
                                    {{ $notification->title }}
                                </span>

                                <span class="prio-badge">
                                    {{ ucfirst($notification->type ?? 'Info') }}
                                </span>

                            </div>

                            <div class="notif-time">
                                {{ $notification->created_at->diffForHumans() }}
                            </div>

                        </div>

                    </div>

                @endforeach

            </div>

        </div>
    </div>

    <!-- ═══════════ 8 & 9. SYSTEM INFORMATION + SERVER HEALTH ═══════════ -->
    <div class="sec-hdr">
        <div>
            <h2>System & Infrastructure</h2>
            <div class="sec-sub">
                Live environment status
            </div>
        </div>
    </div>

    <div class="info-health-row">
        <div class="nx-card">
            <div class="nx-card-hdr"><div>
                <div class="nx-card-title">
                    System Information
                </div>
                <div class="nx-card-sub">
                    Current environment configuration
                </div>
            </div>
        </div>
        <div class="info-list">

            @foreach($systemInfo as $key => $value)

            <div class="info-row">
                <span class="info-k">
                    {{ Str::headline(str_replace('_',' ',$key)) }}
                </span>

                <span class="info-v mono">
                    {{ $value }}
                </span>
            </div>

            @endforeach

        </div>
    </div>

    <div class="nx-card">
        <div class="nx-card-hdr"><div>
            <div class="nx-card-title">
                Server Health
            </div>
            <div class="nx-card-sub">Checked 2 minutes ago</div>
        </div>
    </div>
    <div class="nx-card-body">
        <div class="health-grid">
            @foreach($serverHealth as $health)
                <div class="health-card">

                    <span class="health-dot hd-{{ $health[2] }}"></span>

                    <div>
                        <div class="health-name">
                            {{ $health[0] }}
                        </div>

                        <div class="health-status hs-{{ $health[2] }}">
                            {{ $health[1] }}
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script>

        /* ──────────────────────────────────────────────────
        ENTERPRISE DASHBOARD — GREETING, CLOCK, FOOTER YEAR
        ────────────────────────────────────────────────── */

        function pad (n) { return n < 10 ? '0' + n : '' + n; }

        function updateClock () {
        var now = new Date();
        var h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
        var ampm = h >= 12 ? 'PM' : 'AM';
        var h12 = h % 12 === 0 ? 12 : h % 12;
        $('#wcClock').text(pad(h12) + ':' + pad(m) + ':' + pad(s) + ' ' + ampm);

        var days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        $('#wcDate').text(days[now.getDay()] + ', ' + months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear());
        }

        updateClock();
        setInterval(updateClock, 1000);
        $('#ftYear').text(new Date().getFullYear());

        /* ──────────────────────────────────────────────────
        COUNTER ANIMATION — stat card numbers
        ────────────────────────────────────────────────── */
        function animateCounters () {
        $('.estat-val[data-count]').each(function () {
        var $el    = $(this);
        var target = parseInt($el.data('count'), 10) || 0;
        var start  = 0;
        var dur    = 900;
        var t0     = null;

        function step (ts) {
        if (!t0) t0 = ts;
        var progress = Math.min((ts - t0) / dur, 1);
        var eased    = 1 - Math.pow(1 - progress, 3);
        var val      = Math.round(start + (target - start) * eased);
        $el.text(val.toLocaleString());
        if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
        });
        }

        /* ──────────────────────────────────────────────────
        PROGRESS / MINI BAR FILL ANIMATION
        ────────────────────────────────────────────────── */
        function animateBars () {
        $('.estat-bar > span, .prog-fill').each(function () {
        var $el = $(this);
        var w   = $el.data('w');
        setTimeout(function () { $el.css('width', w + '%'); }, 120);
        });
        }

        animateCounters();
        animateBars();

        function makeLineOpts () {
            return {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: ttDefaults(),
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: tc(), font: { size: 11, family: 'Plus Jakarta Sans' } }, border: { display: false } },
                y: { grid: { color: gc() }, ticks: { color: tc(), font: { size: 11, family: 'Plus Jakarta Sans' }, callback: function(v){ return '$'+(v>=1000?(v/1000).toFixed(0)+'k':v); } }, border: { display: false } }
            }
            };
        }

        function makeBarOpts () {
            return {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'top', align: 'end', labels: { color: tc(), font: { size: 11 }, boxWidth: 10, boxHeight: 10, usePointStyle: true, pointStyle: 'rectRounded' } },
                tooltip: ttDefaults(),
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: tc(), font: { size: 11 } }, border: { display: false } },
                y: { grid: { color: gc() }, ticks: { color: tc(), font: { size: 11 } }, border: { display: false } }
            }
            };
        }

        function ttDefaults () {
            return { backgroundColor: '#101116', titleColor: '#eeeef5', bodyColor: '#7e8099', borderColor: '#232430', borderWidth: 1, padding: 10 };
        }

        function refreshChartColors () {
            [window._sc, window._bc].forEach(function(c) {
            if (!c) return;
            c.options.scales.x.ticks.color = tc();
            c.options.scales.y.ticks.color = tc();
            c.update('none');
            });
        }

        $('#periodToggle').on('click', '.pt-btn', function () {
            $('#periodToggle .pt-btn').removeClass('active');
            $(this).addClass('active');
            var d = $(this).data('period') === 'weekly' ? weeklyData : monthlyData;
            window._sc.data.labels            = d.labels;
            window._sc.data.datasets[0].data  = d.revenue;
            window._sc.update();
        });

        const day30Labels = @json($labels);
        const activityData = @json($activityData);
        const loginData = @json($loginData);

        new Chart(document.getElementById('activityChart30'), {
            type: 'bar',
            data: {
                labels: day30Labels,
                datasets: [{
                    label: 'Activity',
                    data: activityData,
                    backgroundColor: 'rgba(101,103,245,.7)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        new Chart(document.getElementById('loginChart30'), {
            type: 'line',
            data: {
                labels: day30Labels,
                datasets: [{
                    label: 'Logins',
                    data: loginData,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22,163,74,.1)',
                    fill: true,
                    tension: .35,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
@endpush
