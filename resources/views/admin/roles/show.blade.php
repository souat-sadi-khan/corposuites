<div class="modal-header fm-modal-head">
    <div>
        <h5 class="modal-title">
            <i class="ri-shield-user-line me-2"></i>
            Role Details
        </h5>
        <p class="mb-0">
            View role information and assigned permissions
        </p>
    </div>

    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body fm-modal-body">

    {{-- Role Information --}}
    <div class="card border-0 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border) !important; border-radius: 12px;">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h4 class="mb-1" style="color: var(--tx-1);">
                        {{ $role->name }}
                    </h4>

                    <div class="small" style="color: var(--tx-3);">
                        {{ $role->notes ?: 'No description available.' }}
                    </div>
                </div>

                @if($role->status)
                    <span class="badge-s bs-done">
                        Active
                    </span>
                @else
                    <span class="badge-s bs-canc">
                        Inactive
                    </span>
                @endif
            </div>

            <hr style="border-color: var(--border-lt);">

            <div class="row text-center g-3">
                <div class="col-4">
                    <div class="fw-bold fs-5" style="color: var(--tx-1);">
                        {{ $role->permissions->count() }}
                    </div>

                    <small style="color: var(--tx-3);">
                        Permissions
                    </small>
                </div>

                <div class="col-4">
                    <div class="fw-bold" style="color: var(--tx-1);">
                        {{ $role->created_at->format('d M Y') }}
                    </div>

                    <small style="color: var(--tx-3);">
                        Created
                    </small>
                </div>

                <div class="col-4">
                    <div class="fw-bold" style="color: var(--tx-1);">
                        {{ $role->updated_at->format('d M Y') }}
                    </div>

                    <small style="color: var(--tx-3);">
                        Updated
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 fw-semibold" style="color: var(--tx-1);">
            Assigned Permissions
        </h6>

        @if($role->permissions->count())
            <span class="perm-count" style="position: static;">
                {{ count($groupedPermissions) }} {{ Str::plural('module', count($groupedPermissions)) }} &middot; {{ $role->permissions->count() }} {{ Str::plural('permission', $role->permissions->count()) }}
            </span>
        @endif
    </div>

    @if(empty($groupedPermissions))

        <div class="perm-view-empty">
            <i class="ri-shield-cross-line"></i>
            <span>This role has no permissions assigned yet.</span>
        </div>

    @else

        <div class="perm-view" id="permView">

            @foreach($groupedPermissions as $moduleLabel => $menus)

                @php
                    $moduleTotal = collect($menus)->flatten()->count();
                @endphp

                <div class="perm-view-module {{ $loop->first ? '' : 'collapsed' }}">

                    <div class="perm-view-module-head" data-role="perm-view-toggle">

                        <span class="perm-view-module-name">
                            {{ $moduleLabel }}
                        </span>

                        <span class="perm-badge is-active">
                            {{ $moduleTotal }}
                        </span>

                        <i class="ri-arrow-down-s-line perm-chevron"></i>

                    </div>

                    <div class="perm-view-module-body">

                        @foreach($menus as $menuPrefix => $menuPermissions)

                            <div class="perm-view-menu">

                                <span class="perm-view-menu-name">
                                    {{ toWord($menuPrefix) }}
                                </span>

                                <div class="perm-view-grid">

                                    @foreach($menuPermissions as $permission)

                                        <span class="perm-view-tag">
                                            <i class="ri-checkbox-circle-fill"></i>
                                            {{ \Illuminate\Support\Str::headline(\Illuminate\Support\Str::afterLast($permission->name, '.')) }}
                                        </span>

                                    @endforeach

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endforeach

        </div>

    @endif

</div>

<div class="modal-footer fm-modal-foot">
    <span class="fm-foot-note">
        <i class="ri-information-line"></i>
        This page is read-only.
    </span>

    <button type="button" class="btn-nx-outline" data-bs-dismiss="modal">
        <i class="ri-close-line me-1"></i>
        Close
    </button>
</div>

<script>
(function () {
    const root = document.getElementById('permView');
    if (!root) {
        return;
    }

    root.querySelectorAll('[data-role="perm-view-toggle"]').forEach(function (header) {
        header.addEventListener('click', function () {
            const module = header.closest('.perm-view-module');
            if (module) {
                module.classList.toggle('collapsed');
            }
        });
    });
})();
</script>
