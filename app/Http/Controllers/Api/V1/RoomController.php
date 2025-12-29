<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Http\Resources\Api\V1\RoomResource;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $rooms = Room::all();
        return RoomResource::collection($rooms);
    }

    public function show(Room $room)
    {
        return new RoomResource($room);
    }
}
