<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Http\Resources\Api\V1\RoomResource;
use Illuminate\Support\Facades\Log;

class RoomController extends Controller
{
    public function index()
    {
        return RoomResource::collection(Room::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'facilities' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $room = Room::create($data);

        Log::info('Room created', [
            'event' => 'room.created',
            'room_id' => $room->id,
            'admin_id' => auth()->id(),
        ]);

        return new RoomResource($room);
    }

    public function show(Room $room)
    {
        return new RoomResource($room);
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'capacity' => 'sometimes|integer|min:1',
            'facilities' => 'nullable|string',
            'description' => 'nullable|string',
        ]);
        $room->update($data);

        Log::info('Room updated', [
            'event' => 'room.updated',
            'room_id' => $room->id,
            'admin_id' => auth()->id(),
            'changes' => $data,
        ]);

        return new RoomResource($room->fresh());
    }

    public function destroy(Room $room)
    {
        $room->delete();

        Log::info('Room deleted', [
            'event' => 'room.deleted',
            'room_id' => $room->id,
            'admin_id' => auth()->id(),
        ]);

        return response()->json(null, 204);
    }
}
