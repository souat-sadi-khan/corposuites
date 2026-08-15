@if(Auth::guard('admin')->user()->hasPermissionTo('activity-log.details'))
    <button class="tl-icon-btn side-offcanvas" data-url="{{ route('admin.activity.show', $row['id']) }}" title="View Details">
        <i class="ri-eye-line"></i>
    </button>
@endif
