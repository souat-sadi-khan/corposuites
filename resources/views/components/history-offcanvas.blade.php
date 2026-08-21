@props([
    'title',
    'subtitle' => 'History',
    'stats' => [],
    'filterAction' => null,
    'exportLinks' => [],
])

{{--
    Reusable "detail history" offcanvas shell.

    Used by any module that needs to show a record's related history
    (e.g. Salary Component usage, Leave Type usage) inside the shared
    #sideForm offcanvas: a stat summary row, an optional date-range
    filter, an optional row of export links, and a table passed in
    via the default slot.

    The date-range filter (when $filterAction is given) is wired up
    globally in main.js against the ".hoc-filter-form" class, so no
    per-page JS is required to make it work.
--}}

<div class="offcanvas-header hoc-header">
    <div>
        <h5 class="offcanvas-title">{{ $title }}</h5>
        <p>{{ $subtitle }}</p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
</div>

<div class="offcanvas-body hoc-body">

    @if(count($stats))
        <div class="hoc-stats hoc-stats-{{ count($stats) }}">
            @foreach($stats as $stat)
                <div class="hoc-stat-card{{ !empty($stat['accent']) ? ' hoc-stat-accent' : '' }}">
                    <div class="hoc-stat-value">{{ $stat['value'] }}</div>
                    <div class="hoc-stat-label">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if($filterAction)
        <form class="hoc-filter-form" data-base-url="{{ $filterAction }}">
            <div class="hoc-filter-field">
                <label>From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
            </div>
            <div class="hoc-filter-field">
                <label>To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn-nx-primary btn-sm hoc-filter-submit">
                <i class="ri-filter-3-line"></i> Filter
            </button>
            @if(request('from') || request('to'))
                <a href="{{ strtok($filterAction, '?') }}" class="hoc-filter-clear">
                    <i class="ri-close-line"></i> Clear
                </a>
            @endif
        </form>
    @endif

    @if(count($exportLinks))
        <div class="hoc-export-row">
            @foreach($exportLinks as $link)
                <a class="btn-nx-outline btn-sm"
                   href="{{ $link['url'] }}"
                   @if(!empty($link['target'])) target="{{ $link['target'] }}" @endif>
                    <i class="{{ $link['icon'] ?? 'ri-download-2-line' }}"></i> {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="hoc-table-wrap">
        {{ $slot }}
    </div>

</div>
