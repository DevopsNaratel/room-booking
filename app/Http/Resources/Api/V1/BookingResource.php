<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'start_time' => optional($this->start_time)->toDateTimeString(),
            'end_time' => optional($this->end_time)->toDateTimeString(),
            'status' => $this->status,
            'purpose' => $this->purpose,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
