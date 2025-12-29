<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Http\Resources\Api\V1\BookingResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $bookings = Booking::with('room')->where('user_id', $user->id)->latest()->get();
        return BookingResource::collection($bookings);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'purpose' => 'nullable|string',
        ]);

        $booking = Booking::create([
            'user_id' => Auth::id(),
            'room_id' => $data['room_id'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'purpose' => $data['purpose'] ?? null,
            'status' => 'pending',
        ]);

        Log::info('Booking created', [
            'event' => 'booking.created',
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
        ]);

        return new BookingResource($booking->load('room'));
    }

    public function show(Booking $booking)
    {
        $user = Auth::user();
        if ($booking->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return new BookingResource($booking->load('room'));
    }

    public function update(Request $request, Booking $booking)
    {
        $user = Auth::user();
        if ($booking->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Only pending bookings can be modified'], 422);
        }

        $data = $request->validate([
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'purpose' => 'nullable|string',
        ]);

        $booking->update($data);
        Log::info('Booking updated', [
            'event' => 'booking.updated',
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'changes' => $data,
        ]);

        return new BookingResource($booking->fresh()->load('room'));
    }

    public function destroy(Booking $booking)
    {
        $user = Auth::user();
        if ($booking->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($booking->status === 'approved') {
            return response()->json(['message' => 'Cannot delete an approved booking'], 422);
        }

        $booking->delete();

        Log::info('Booking deleted', [
            'event' => 'booking.deleted',
            'booking_id' => $booking->id,
            'deleted_by' => $user->id,
        ]);

        return response()->json(null, 204);
    }
}
