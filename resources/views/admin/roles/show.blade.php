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
    <div class="card border-0 bg-light mb-4">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h4 class="mb-1">
                        {{ $role->name }}
                    </h4>

                    <div class="text-muted small">
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

            <hr>

            <div class="row text-center g-3">
                <div class="col-4">
                    <div class="fw-bold fs-5">
                        {{ $role->permissions->count() }}
                    </div>

                    <small class="text-muted">
                        Permissions
                    </small>
                </div>

                <div class="col-4">
                    <div class="fw-bold">
                        {{ $role->created_at->format('d M Y') }}
                    </div>

                    <small class="text-muted">
                        Created
                    </small>
                </div>

                <div class="col-4">
                    <div class="fw-bold">
                        {{ $role->updated_at->format('d M Y') }}
                    </div>

                    <small class="text-muted">
                        Updated
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 fw-semibold">
            Assigned Permissions
        </h6>
        <span class="badge bg-primary rounded-pill">
            {{ $role->permissions->count() }}
        </span>
    </div>

    <div class="accordion" id="permissionAccordion">
        @foreach(split_name($role->permissions) as $module => $permissions)
            <div class="accordion-item mb-2 border rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ !$loop->first ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#module{{ $loop->index }}">
                        <div class="d-flex justify-content-between w-100 me-3">
                            <span class="fw-semibold">
                                {{ $module }}
                            </span>

                            <span class="badge bg-secondary">
                                {{ count($permissions) }}
                            </span>
                        </div>
                    </button>
                </h2>

                <div id="module{{ $loop->index }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#permissionAccordion">

                    <div class="accordion-body py-3">
                        <div class="row g-2">
                            @foreach($permissions as $permission)
                                <div class="col-md-6">
                                    <div class="border rounded px-3 py-2 bg-white d-flex align-items-center">
                                        <i class="ri-checkbox-circle-fill text-success me-2"></i>

                                        <span class="small">
                                            {{ toSpan($permission) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
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
