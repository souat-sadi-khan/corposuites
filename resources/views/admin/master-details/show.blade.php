@php
    $actionMeta = [
        'create' => ['icon' => 'ri-add-line', 'bg' => 'bg-success-subtle', 'color' => 'text-success'],
        'update' => ['icon' => 'ri-pencil-line', 'bg' => 'bg-info-subtle', 'color' => 'text-info'],
        'delete' => ['icon' => 'ri-delete-bin-line', 'bg' => 'bg-danger-subtle', 'color' => 'text-danger'],
    ];
@endphp

<div class="offcanvas-header">
    <div>
        <h5 class="offcanvas-title">{{ $master->name }}</h5>
        <p>
            {{ $title }}
            &middot;
            <span class="badge {{ $master->status ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                {{ $master->status ? 'Active' : 'Inactive' }}
            </span>
        </p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
</div>

<div class="offcanvas-body px-3">

    <div class="fm-grid mb-4">
        <div class="fm-field fm-full">
            <label>Description</label>
            <div>{{ $master->description ?: 'No description provided.' }}</div>
        </div>

        <div class="fm-field">
            <label>Assigned Employees</label>
            <div class="fw-semibold">{{ $employees->count() }}</div>
        </div>

        <div class="fm-field">
            <label>Created</label>
            <div>{{ $master->created_at?->format('d M Y, h:i A') ?? '-' }}</div>
        </div>

        @if($master instanceof \App\Models\Shift)
            <div class="fm-field">
                <label>Working Hours</label>
                <div class="fw-semibold">
                    {{ \Carbon\Carbon::parse($master->start_time)->format('h:i A') }}
                    &ndash;
                    {{ \Carbon\Carbon::parse($master->end_time)->format('h:i A') }}
                </div>
            </div>
        @elseif($master instanceof \App\Models\Designation)
            <div class="fm-field">
                <label>Department</label>
                <div class="fw-semibold">{{ $master->department?->name ?? 'Not assigned' }}</div>
            </div>
        @endif
    </div>

    @if($designations->isNotEmpty())
        <h6 class="mb-2">Designations in this Department</h6>
        <table class="ractivity-tbl w-100 mb-4">
            <thead>
                <tr>
                    <th>Designation</th>
                    <th class="text-end">Employees</th>
                </tr>
            </thead>
            <tbody>
                @foreach($designations as $designation)
                    <tr>
                        <td>{{ $designation->name }}</td>
                        <td class="text-end">
                            <span class="badge bg-light text-dark">{{ $designation->employees_count }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <ul class="nav nav-tabs flex-nowrap overflow-auto" id="masterDetailTabs" role="tablist" style="white-space:nowrap;">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-master-employees" type="button">
                Employees <span class="badge bg-light text-dark ms-1">{{ $employees->count() }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-master-activity" type="button">
                Activity <span class="badge bg-light text-dark ms-1">{{ $activities->count() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content pt-3">
        {{-- Employees --}}
        <div class="tab-pane fade show active" id="tab-master-employees">
            <table class="ractivity-tbl w-100">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Joined</th>
                        <th>Login Role</th>
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-semibold flex-shrink-0"
                                          style="width:32px;height:32px;font-size:.75rem;">
                                        {{ strtoupper(substr($employee->full_name, 0, 1)) }}
                                    </span>
                                    <div>
                                        <div class="fw-semibold">{{ $employee->full_name }}</div>
                                        <small class="text-muted">{{ $employee->employee_code }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $employee->date_of_joining?->format('d M Y') ?? '-' }}</td>
                            <td>
                                @if($employee->admin)
                                    @forelse($employee->admin->getRoleNames() as $role)
                                        <span class="badge bg-info-subtle text-info">{{ $role }}</span>
                                    @empty
                                        <span class="text-muted small">No role assigned</span>
                                    @endforelse
                                @else
                                    <span class="text-muted small">No login account</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="badge {{ $employee->status ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                    {{ $employee->status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                <div class="text-center py-4">
                                    <img src="{{ asset('assets/images/nothing-to-show.svg') }}" class="img-fluid mb-2" style="max-width:150px">
                                    <p class="text-muted mb-0">No employees are currently assigned.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Activity --}}
        <div class="tab-pane fade" id="tab-master-activity">
            @forelse($activities as $activity)
                @php $meta = $actionMeta[$activity->action] ?? ['icon' => 'ri-history-line', 'bg' => 'bg-secondary-subtle', 'color' => 'text-secondary']; @endphp
                <div class="d-flex gap-3 mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle {{ $meta['bg'] }} {{ $meta['color'] }} flex-shrink-0"
                         style="width:32px;height:32px;">
                        <i class="{{ $meta['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="fw-semibold small">{{ $activity->description ?: ucfirst($activity->action) }}</div>
                        <small class="text-muted">{{ $activity->created_at->format('d M Y, h:i A') }} &middot; {{ $activity->admin?->name ?? 'System' }}</small>
                    </div>
                </div>
            @empty
                <div class="text-center py-4">
                    <img src="{{ asset('assets/images/nothing-to-show.svg') }}" class="img-fluid mb-2" style="max-width:150px">
                    <p class="text-muted mb-0">No recorded activity yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
