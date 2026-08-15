@php
    $route = $node['_route'] ?? null;
    $children = $node['_children'] ?? [];
    $hasChildren = !empty($children);
@endphp

<li class="tree-node" data-haschildren="{{ $hasChildren ? 'true' : 'false' }}">

    <div class="tree-row">

        <button type="button" class="tree-caret {{ !$hasChildren ? 'no-children' : '' }}">
            @if ($hasChildren)
                <i class="ri-arrow-right-s-line"></i>
            @endif
        </button>

        <i class="tree-icon ri-route-line"></i>

        <span class="tree-label">
            {{ Str::title(str_replace('-', ' ', $name)) }}
        </span>

        @if ($route)
            <span class="tree-path">
                {{ $route['uri'] }}
            </span>

            @foreach ($route['methods'] as $method)
                <span class="tree-badge tree-method method-{{ strtolower($method) }}">
                    {{ $method }}
                </span>
            @endforeach
        @endif
    </div>

    @if ($hasChildren)
        <ul class="tree-children">
            @foreach ($children as $childName => $child)
                @include('admin.settings.sitemap-node', [
                    'name' => $childName,
                    'node' => $child,
                ])
            @endforeach
        </ul>
    @endif

</li>
