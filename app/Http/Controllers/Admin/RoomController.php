<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rooms = Room::all();
        return view('admin.rooms.index', compact('rooms'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.rooms.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'facilities' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $room = Room::create($request->all());

        Log::info('Room created', [
            'event'   => 'room.created',
            'room_id' => $room->id,
            'name'    => $room->name,
        ]);

        return redirect()->route('admin.rooms.index')->with('success', 'Room created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room)
    {
        return view('admin.rooms.show', compact('room'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Room $room)
    {
        return view('admin.rooms.edit', compact('room'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room $room)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'facilities' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $room->update($request->all());

        Log::info('Room updated', [
            'event'   => 'room.updated',
            'room_id' => $room->id,
            'name'    => $room->name,
        ]);

        return redirect()->route('admin.rooms.index')->with('success', 'Room updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        $roomId = $room->id;
        $roomName = $room->name;

        $room->delete();

        Log::info('Room deleted', [
            'event'   => 'room.deleted',
            'room_id' => $roomId,
            'name'    => $roomName,
        ]);

        return redirect()->route('admin.rooms.index')->with('success', 'Room deleted successfully.');
    }
}
