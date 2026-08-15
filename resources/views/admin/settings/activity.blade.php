<div class="offcanvas-header">
    <h5 class="offcanvas-title">Detailed Activity</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
</div>

<!-- Scrollable body -->
<div class="offcanvas-body">

    <div class="row">
        <div class="col-md-12 table-responsive">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th>Module</th>
                        <td>{{ $activity->module }}</td>
                    </tr>
                    <tr>
                        <th>Action</th>
                        <td class="text-capitalize">{{ $activity->action }}</td>
                    </tr>
                    <tr>
                        <th>Model</th>
                        <td>{{ $activity->model ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Model ID</th>
                        <td>{{ $activity->model_id ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td>{{ $activity->description }}</td>
                    </tr>
                    <tr>
                        <th>IP Address</th>
                        <td>{{ $activity->ip_address }}</td>
                    </tr>
                    <tr>
                        <th>URL</th>
                        <td>{{ $activity->url }}</td>
                    </tr>
                    <tr>
                        <th>Method</th>
                        <td>
                            @if ($activity->method == 'get')
                                <span class="badge bg-info">GET</span>
                            @elseif($activity->method == 'POST')
                                <span class="badge bg-success">POST</span>
                            @elseif($activity->method == 'PATCH')
                                <span class="badge bg-primary">PATCH</span>
                            @else
                                <span class="badge bg-danger">DELETE</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Actor Type</th>
                        <td>{{ $activity->actor_type == 'user' ? 'Admin User' : 'Normal User' }}</td>
                    </tr>
                    <tr>
                        <th>Actor</th>
                        <td>{{ $activity->actor_id ? App\Models\Admin::find($activity->actor_id)->name : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>{{ $activity->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                </tbody>
            </table>

            @php
                $jsonFields = ['old_data','new_data','meta'];
            @endphp

            @foreach($jsonFields as $field)
                @if($activity->$field)
                    <div class="mb-3">
                        <h6 class="fw-bold text-primary text-uppercase">{{ str_replace('_',' ',$field) }}</h6>
                        <pre class="p-2 bg-light border rounded" style="max-height:300px;overflow:auto;">
{{ json_encode($activity->$field, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                        </pre>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>

<!-- Footer with buttons -->
<div class="offcanvas-footer d-flex justify-content-between align-items-center p-3 border-top">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">
        <i class="bx fs-5 lh-0 bx-x me-2"></i>
        Close
    </button>
    {{-- <button type="button" class="btn btn-primary">Create / Update</button> --}}
</div>
