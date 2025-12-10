<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Http\Resources\ActivityLogResource;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query()->orderBy('created_at', 'desc');

        if ($request->action) {
            $query->where('action', $request->action);
        }

        if ($request->table_name) {
            $query->where('table_name', $request->table_name);
        }

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->per_page ?? 20;

        $logs = $query->paginate($perPage)->withQueryString();

        return ActivityLogResource::collection($logs)
            ->additional([
                'status' => 'success',
                'message' => 'Activity logs retrieved successfully.',
            ]);
    }
}
