<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            ActivityLog::create([
                'user_id'    => Auth::id(),
                'action'     => 'CREATE',
                'table_name' => $model->getTable(),
                'record_id'  => $model->id,
                'after_data' => $model->toArray(),
                'description'=> 'Created new record',
            ]);
        });

        static::updated(function ($model) {
            ActivityLog::create([
                'user_id'    => Auth::id(),
                'action'     => 'UPDATE',
                'table_name' => $model->getTable(),
                'record_id'  => $model->id,
                'before_data'=> $model->getOriginal(),
                'after_data' => $model->getChanges(),
                'description'=> 'Updated record',
            ]);
        });

        static::deleted(function ($model) {
            ActivityLog::create([
                'user_id'    => Auth::id(),
                'action'     => 'DELETE',
                'table_name' => $model->getTable(),
                'record_id'  => $model->id,
                'before_data'=> $model->toArray(),
                'description'=> 'Deleted record',
            ]);
        });
    }
}
