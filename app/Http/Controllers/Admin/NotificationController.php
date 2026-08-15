<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Notification;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::orderBy('created_at', 'desc')->take(5)->get();
        $unreadCount = Notification::where('is_read', false)->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    // (SSE)
    public function stream()
    {
        $notifications = Notification::where('is_read', false)->get();

        if ($notifications->isNotEmpty()) {
            foreach ($notifications as $notification) {
                $notification->update(['is_read' => true]);
            }
        }

        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        Notification::where('id', $id)->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function createDummyLog()
    {
        $log = ActivityLog::create([
            'actor_type'  => 'Admin',
            'actor_id'    => auth()->guard('admin')->id(),
            'module'      => 'Order Management',
            'action'      => 'Create',
            'model'       => 'Order',
            'model_id'    => rand(1000, 9999),
            'description' => 'New order #ORD-' . rand(1000, 9999) . ' has been placed successfully.',
            'old_data'    => null,
            'new_data'    => ['status' => 'pending', 'total' => 2500],
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'url'         => request()->fullUrl(),
            'method'      => request()->method(),
            'meta'        => ['source' => 'web_test']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dummy activity log created successfully!',
            'data'    => $log
        ]);
    }

    public function notificationsPage(Request $request)
    {
        if ($request->ajax()) {
            $notifications = Notification::query()->orderBy('created_at', 'desc');

            return DataTables::of($notifications)
                ->addIndexColumn()
                ->editColumn('created_at', function ($model) {
                    return $model->created_at->diffForHumans();
                })
                ->editColumn('is_read', function ($model) {
                    return $model->is_read ? '<span class="badge-s bs-done">Read</span>' : '<span class="badge-s bs-pend">Unread</span>';
                })
                ->addColumn('action', function ($model) {
                    return '<div class="tl-actions"><button class="tl-icon-btn danger delete-single-btn" data-id="' . $model->id . '"> <i class="ri-delete-bin-line"></i> </button></div>';
                })
                ->rawColumns(['action', 'is_read'])
                ->make(true);
        }

        return view('admin.notifications.index');
    }

    public function deleteSelected(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:notifications,id'
        ]);

        try {
            $ids = $request->input('ids');

            Notification::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => count($ids) . ' notifications deleted successfully.'
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while trying to delete the items.'
            ], 500);
        }
    }

    public function destroy($id)
    {
        Notification::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function deleteAll()
    {
        Notification::query()->delete();
        return response()->json(['success' => true]);
    }
}
