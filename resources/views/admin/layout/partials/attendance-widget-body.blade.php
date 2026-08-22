@php
    $awMeta = [
        'not_checked_in' => ['icon' => 'ri-time-line'],
        'checked_in' => ['icon' => 'ri-checkbox-circle-fill'],
        'late' => ['icon' => 'ri-alarm-warning-fill'],
        'checked_out' => ['icon' => 'ri-flag-2-fill'],
        'on_leave' => ['icon' => 'ri-plane-fill'],
        'holiday' => ['icon' => 'ri-sun-fill'],
        'weekly_off' => ['icon' => 'ri-moon-clear-fill'],
        'absent' => ['icon' => 'ri-close-circle-fill'],
    ];
    $awIcon = $awMeta[$w['state']]['icon'] ?? 'ri-time-line';
@endphp

<div class="aw-content" data-state="{{ $w['state'] }}" data-label="{{ $w['label'] }}" data-time="{{ $w['check_in'] }}">
    <div class="aw-status-row">
        <span class="aw-pill aw-pill-{{ $w['state'] }}">
            <i class="{{ $awIcon }}"></i>
            {{ $w['label'] }}
        </span>
        @if($w['note'])
            <span class="aw-note">{{ $w['note'] }}</span>
        @endif
    </div>

    <div class="aw-grid">
        <div class="aw-item">
            <span class="aw-item-label"><i class="ri-login-circle-line"></i> Check In</span>
            <span class="aw-item-value">{{ $w['check_in'] ?? '--' }}</span>
        </div>
        <div class="aw-item">
            <span class="aw-item-label"><i class="ri-logout-circle-line"></i> Check Out</span>
            <span class="aw-item-value">{{ $w['check_out'] ?? '--' }}</span>
        </div>
        <div class="aw-item">
            <span class="aw-item-label"><i class="ri-hourglass-line"></i> Worked</span>
            <span class="aw-item-value">{{ $w['worked_label'] ?? '--' }}</span>
        </div>
        <div class="aw-item">
            <span class="aw-item-label"><i class="ri-shining-2-line"></i> Shift</span>
            <span class="aw-item-value">{{ $w['shift_name'] }}</span>
        </div>
    </div>

    <div class="aw-action">
        @if($w['can_check_in'])
            <button type="button" class="aw-btn aw-btn-in" id="awCheckInBtn">
                <i class="ri-login-circle-fill"></i> Check In
            </button>
        @elseif($w['can_check_out'])
            <button type="button" class="aw-btn aw-btn-out" id="awCheckOutBtn">
                <i class="ri-logout-circle-fill"></i> Check Out
            </button>
        @else
            <div class="aw-completed">
                <i class="ri-checkbox-circle-fill"></i> {{ $w['label'] }}
            </div>
        @endif
    </div>

    <div class="aw-message" id="awMessage"></div>
</div>
