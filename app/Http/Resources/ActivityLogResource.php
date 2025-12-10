<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'action'      => $this->action,
            'table_name'  => $this->table_name,
            'record_id'   => $this->record_id,
            'user_id'     => $this->user_id,
            'user_name'   => $this->whenLoaded('user', fn () => $this->user->name),
            'before_data' => $this->before_data,
            'after_data'  => $this->after_data,
            'description' => $this->description,
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
