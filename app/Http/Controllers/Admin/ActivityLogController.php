<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Admin;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    public function index()
    {
        $admins = Admin::select(
            'id',
            'name'
        )->get();


        $modules = ActivityLog::select('module')
            ->groupBy('module')
            ->pluck('module');


        $actions = ActivityLog::select('action')
            ->groupBy('action')
            ->pluck('action');


        return view(
            'admin.logs.index',
            compact(
                'admins',
                'modules',
                'actions'
            )
        );
    }

    public function data(Request $request)
    {
        $query = ActivityLog::with('admin')
            ->select([
                'id',
                'actor_id',
                'module',
                'url',
                'method',
                'user_agent',
                'old_data',
                'new_data',
                'meta',
                'action',
                'description',
                'model',
                'model_id',
                'ip_address',
                'created_at'
            ]);

        if($request->actor_id)
        {
            $query->where(
                'actor_id',
                $request->actor_id
            );
        }

        if($request->module)
        {
            $query->where(
                'module',
                $request->module
            );
        }

        if($request->action)
        {
            $query->where(
                'action',
                $request->action
            );
        }

        if($request->date_from)
        {
            $query->whereDate(
                'created_at',
                '>=',
                $request->date_from
            );
        }

        if($request->date_to)
        {
            $query->whereDate(
                'created_at',
                '<=',
                $request->date_to
            );
        }


        return DataTables::eloquent($query)
            ->addColumn(
                'actor',
                function($row){

                    return $row->admin
                    ?
                    $row->admin->name
                    :
                    'System';

                }
            )
            ->addColumn(
                'target',
                function($row){

                    if(!$row->model)
                        return '-';


                    return class_basename(
                        $row->model
                    )
                    .
                    " #".$row->model_id;

                }
            )
            ->addColumn(
                'action_badge',
                function($row){

                    $class = match($row->action)
                    {

                        'create'
                        =>
                        'success',


                        'update'
                        =>
                        'primary',


                        'delete'
                        =>
                        'danger',


                        'login'
                        =>
                        'info',


                        default
                        =>
                        'secondary'

                    };


                    return '
                    <span class="badge bg-'.$class.'">
                        '.strtoupper($row->action).'
                    </span>';

                }
            )
            ->editColumn(
                'created_at',
                function($row){

                    return $row->created_at
                    ->format('d M Y h:i A');

                }
            )
            ->addColumn(
                'action',
                function($row){

                    return view(
                        'admin.logs.action',
                        compact('row')
                    );

                }
            )
            ->rawColumns([
                'action_badge',
                'action'
            ])
            ->make(true);
    }

    public function show($id)
    {
        $log = ActivityLog::with('admin') ->findOrFail($id);

        return view(
            'admin.logs.show',
            compact('log')
        );
    }
}
