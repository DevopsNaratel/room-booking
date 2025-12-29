<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Http\Resources\Api\V1\BookingResource;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['user', 'room'])->latest()->get();
        return BookingResource::collection($bookings);
    }

    public function approve(Booking $booking)
    {
        if ($booking->status === 'approved') {
            return response()->json(['message' => 'Already approved'], 422);
        }
        $booking->update(['status' => 'approved']);

        Log::info('Booking approved', [
            'event' => 'booking.approved',
            'booking_id' => $booking->id,
            'admin_id' => auth()->id(),
        ]);
        return new BookingResource($booking->fresh()->load(['user', 'room']));
    }

    public function reject(Booking $booking)
    {
        if ($booking->status === 'rejected') {
            return response()->json(['message' => 'Already rejected'], 422);
        }
        $booking->update(['status' => 'rejected']);

        Log::info('Booking rejected', [
            'event' => 'booking.rejected',
            'booking_id' => $booking->id,
            'admin_id' => auth()->id(),
        ]);
        return new BookingResource($booking->fresh()->load(['user', 'room']));
    }
}
