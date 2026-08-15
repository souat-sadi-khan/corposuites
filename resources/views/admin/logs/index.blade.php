@extends('admin.layout.app', ['title' => 'Audit Logs', 'offcanvas'=>'60%'])

@section('content')
    <div class="tl-toolbar">
        <div class="tl-search">
            <i class="ri-search-line"></i>
            <input type="text" id="logSearch" placeholder="Search activity logs">
        </div>

        <div class="tl-filter-wrap">
            <button class="tl-filter-btn" id="logFilterBtn">
                <i class="ri-equalizer-line"></i>
            </button>

<div
class="tl-filter-dd"
id="logFilterDd">


<div class="tl-filter-dd-title">
Filter Logs
</div>



<select id="actor_id"
class="form-select mb-2">

<option value="">
All Admin
</option>


@foreach($admins as $admin)

<option value="{{$admin->id}}">
{{$admin->name}}
</option>

@endforeach

</select>




<select id="module"
class="form-select mb-2">

<option value="">
All Module
</option>


@foreach($modules as $module)

<option value="{{$module}}">
{{ucfirst($module)}}
</option>

@endforeach

</select>





<select id="action"
class="form-select">


<option value="">
All Action
</option>


@foreach($actions as $action)

<option value="{{$action}}">
{{ucfirst($action)}}
</option>


@endforeach

</select>


</div>


</div>





<div class="tl-spacer"></div>



</div>






<div class="nx-card tl-card">


<div class="table-responsive">


<table

id="auditTable"

data-url="{{route('admin.activity.logs.data')}}"

class="tl-table"

width="100%">



<thead>


<tr>


<th class="no-sort tl-check-col">

<input type="checkbox"
id="selectAllChk">

</th>


<th>User</th>

<th>Module</th>

<th>Action</th>

<th>Description</th>

<th>Target</th>

<th>IP</th>

<th>Date</th>


<th></th>


</tr>


</thead>


<tbody></tbody>


</table>


</div>





<div class="tl-footer">


<div
class="tl-info"
id="tlInfo">

</div>



<div class="tl-pagination">


<button
class="tl-page-btn"
id="tlPrev">

<i class="ri-arrow-left-s-line"></i>

</button>



<button
class="tl-page-btn"
id="tlNext">

<i class="ri-arrow-right-s-line"></i>

</button>


</div>


</div>



</div>



@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
    <script src="{{asset('assets/system/js/pages/logs.js')}}"></script>
@endpush
