<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use App\Traits\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ClientController extends Controller
{
    use ActivityLogger;

    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Client::query();

            // Filter by status
            if ($request->status) {
                $statuses = explode(',', $request->status);
                $query->whereIn('status', $statuses);
            }

            // Filter by client type
            if ($request->client_type) {
                $query->where('client_type', $request->client_type);
            }

            // Search
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('client_code', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $query->orderBy('id', 'DESC');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($row) {
                    $checked = $row->status ? 'checked' : '';
                    return '<div class="fm-field"><div class="form-check form-switch"><input data-url="' . route('admin.clients.status', $row->id) . '" class="switch form-check-input" type="checkbox" role="switch" name="status" id="status' . $row->id . '" ' . $checked . ' data-id="' . $row->id . '"></div></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<b class="tl-name-txt">' . e($row->name) . '</b><br><small>' . e($row->client_code) . '</small>';
                })
                ->addColumn('type_badge', function ($row) {
                    $class = $row->client_type === 'company' ? 'bg-primary' : 'bg-info';

                    return '<span class="badge ' . $class . '">' . e($row->client_type_label) . '</span>'
                        . ($row->company_name ? '<br><small>' . e($row->company_name) . '</small>' : '');
                })
                ->addColumn('contact', function ($row) {
                    $lines = [];
                    if ($row->contact_person) {
                        $lines[] = '<b>' . e($row->contact_person) . '</b>';
                    }
                    if ($row->email) {
                        $lines[] = e($row->email);
                    }
                    if ($row->phone) {
                        $lines[] = '<small>' . e($row->phone) . '</small>';
                    }

                    return $lines ? implode('<br>', $lines) : '-';
                })
                ->addColumn('location_label', function ($row) {
                    return $row->location ? e($row->location) : '-';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.clients.action', compact('row'))->render();
                })
                ->rawColumns(['status_badge', 'name', 'type_badge', 'contact', 'action'])
                ->make(true);
        }

        return view('admin.clients.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.clients.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClientRequest $request)
    {
        DB::beginTransaction();

        try {
            $client = $this->clientService->create($request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'clients',
                'action' => 'create',
                'model' => 'Client',
                'model_id' => $client->id,
                'description' => 'Client "' . $client->name . ' (' . $client->client_code . ')" created',
                'new_data' => $client->toArray(),
                'old_data' => null
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Client created successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ClientRequest $request, Client $client)
    {
        DB::beginTransaction();

        try {
            $oldData = $client->toArray();
            $updatedClient = $this->clientService->update($client, $request->validated());

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'clients',
                'action' => 'update',
                'model' => 'Client',
                'model_id' => $client->id,
                'description' => 'Client "' . $updatedClient->name . ' (' . $updatedClient->client_code . ')" updated',
                'new_data' => $updatedClient->toArray(),
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'goto' => route('admin.clients.index'),
                'message' => 'Client updated successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        DB::beginTransaction();

        try {
            $oldData = $client->toArray();

            $this->clientService->delete($client);

            $this->logActivity([
                'actor_type' => 'admin',
                'actor_id' => auth()->guard('admin')->id(),
                'module' => 'clients',
                'action' => 'delete',
                'model' => 'Client',
                'model_id' => $oldData['id'],
                'description' => 'Client "' . $oldData['name'] . ' (' . $oldData['client_code'] . ')" deleted',
                'new_data' => null,
                'old_data' => $oldData
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Client deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update status (AJAX switch toggle)
     */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $model = Client::find($id);
        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Record not found.'
            ]);
        }

        $model->status = $request->input('status');
        $model->save();

        return response()->json([
            'success' => true,
            'message' => 'Record status updated successfully.'
        ]);
    }
}
