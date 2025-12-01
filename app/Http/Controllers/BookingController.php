<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BookingCreatedNotification;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Auth::user()->bookings()->with('room')->latest()->get();
        return view('bookings.index', compact('bookings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'purpose' => 'required|string',
        ]);

        // Check for room availability
        $room = Room::findOrFail($request->room_id);
        $isAvailable = $room->bookings()->where(function ($query) use ($request) {
            $query->where('start_time', '<', $request->end_time)
                  ->where('end_time', '>', $request->start_time);
        })->doesntExist();

        if (!$isAvailable) {
            return back()->withErrors(['message' => 'The room is not available for the selected time slot.']);
        }

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'purpose' => $request->purpose,
            'status' => 'pending', // Default status
        ]);

        // Notify admin
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new BookingCreatedNotification($booking));

        return redirect()->route('my-bookings.index')->with('success', 'Booking created successfully and is awaiting approval.');
    }

    public function edit(Booking $booking)
    {
        if (Auth::id() !== $booking->user_id || $booking->status === 'approved') {
            abort(403);
        }

        $rooms = Room::all();
        return view('bookings.edit', compact('booking', 'rooms'));
    }

    public function update(Request $request, Booking $booking)
    {
        if (Auth::id() !== $booking->user_id || $booking->status === 'approved') {
            abort(403);
        }

        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'purpose' => 'required|string',
        ]);

        $room = Room::findOrFail($request->room_id);
        $isAvailable = $room->bookings()
            ->where('id', '!=', $booking->id)
            ->where(function ($query) use ($request) {
                $query->where('start_time', '<', $request->end_time)
                      ->where('end_time', '>', $request->start_time);
            })->doesntExist();

        if (!$isAvailable) {
            return back()->withErrors(['message' => 'The room is not available for the selected time slot.']);
        }

        $booking->update([
            'room_id' => $request->room_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'purpose' => $request->purpose,
            'status' => 'pending', // Reset status to pending after update
        ]);

        return redirect()->route('my-bookings.index')->with('success', 'Booking updated successfully and is awaiting approval.');
    }

    public function destroy(Booking $booking)
    {
        if (Auth::id() !== $booking->user_id || $booking->status === 'approved') {
            abort(403);
        }

        $booking->delete();

        return redirect()->route('my-bookings.index')->with('success', 'Booking deleted successfully.');
    }
}
