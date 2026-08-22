@foreach($items as $item)
    @php
        $hasChildren = !empty($item['children']);
        $canAccess = !isset($item['permission']) || $item['permission'] === null
            || auth()->guard('admin')->user()?->can($item['permission']);

        if (!$canAccess) {
            continue;
        }

        // Filter children
        $filteredChildren = [];
        if ($hasChildren) {
            $filteredChildren = array_filter($item['children'], function ($child) {
                return !isset($child['permission']) || $child['permission'] === null
                    || auth()->guard('admin')->user()?->can($child['permission']);
            });

            $hasVisibleChildren = !empty($filteredChildren);

            // A group whose every child has been permission-filtered away
            // has nothing left to show — skip it entirely rather than
            // falling through to the "no children" branch below, which
            // would otherwise render it as a dead "#" link (group headers
            // carry no route of their own).
            if (!$hasVisibleChildren) {
                continue;
            }
        } else {
            $hasVisibleChildren = false;
        }

        $itemId = $item['id'] ?? uniqid();
        $subId = $prefix . '-sub-' . $itemId;

        $currentRoute = request()->route()?->getName();
        $isActive = false;

        if (!empty($item['route']) && $item['route'] === $currentRoute) {
            $isActive = true;
        } elseif (!empty($item['url']) && request()->is(ltrim($item['url'], '/'))) {
            $isActive = true;
        } elseif ($hasVisibleChildren) {
            $isActive = checkChildActive($filteredChildren);
        }

        // Generate URL safely
        $href = '#';

        if (!empty($item['route']) && \Illuminate\Support\Facades\Route::has($item['route'])) {
            $href = route($item['route']);
        } elseif (!empty($item['url'])) {
            $href = $item['url'];
        }
    @endphp

    @if($hasVisibleChildren)
        <div class="nav-item-wrap has-sub">
            <div class="nav-row {{ $isActive ? 'is-active is-open' : '' }}"
                 data-target="sub-{{ $subId }}">
                <i class="{{ $item['icon'] ?? 'ri-folder-line' }} n-icon"></i>
                <span class="n-lbl">{{ $item['label'] }}</span>
                <i class="ri-arrow-right-s-line n-arrow"></i>
            </div>

            <div class="nav-sub"
                 id="sub-{{ $subId }}"
                 style="display: {{ $isActive ? 'block' : 'none' }}">

                @include('admin.layout.partials.dynamic_submenu', [
                    'items' => $filteredChildren,
                    'prefix' => $subId
                ])

            </div>
        </div>
    @else
        <div class="nav-item-wrap">
            <a href="{{ $href }}"
               class="nav-row {{ $isActive ? 'is-active' : '' }}"
               data-page="{{ $item['label'] }}">
                <i class="{{ $item['icon'] ?? 'ri-circle-line' }} n-icon"></i>
                <span class="n-lbl">{{ $item['label'] }}</span>
            </a>
        </div>
    @endif
@endforeach
