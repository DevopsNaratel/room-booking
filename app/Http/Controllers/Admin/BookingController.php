<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BookingStatusUpdatedNotification;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'room']);

        // Filter by user name
        if ($request->filled('user')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('user') . '%');
            });
        }

        // Filter by room name
        if ($request->filled('room')) {
            $query->whereHas('room', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('room') . '%');
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('start_time', $request->input('date'));
        }

        $bookings = $query->latest()->paginate(10)->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function approve(Booking $booking)
    {
        $booking->update(['status' => 'approved']);

        // Notify user
        Notification::send($booking->user, new BookingStatusUpdatedNotification($booking));

        Log::info('Booking approved', [
            'event'      => 'booking.approved',
            'booking_id' => $booking->id,
            'admin_id'   => Auth::id(),
            'user_id'    => $booking->user_id,
            'room_id'    => $booking->room_id,
        ]);

        return redirect()->route('admin.bookings.index')->with('success', 'Booking approved successfully.');
    }

    public function reject(Booking $booking)
    {
        $booking->update(['status' => 'rejected']);

        // Notify user
        Notification::send($booking->user, new BookingStatusUpdatedNotification($booking));

        Log::info('Booking rejected', [
            'event'      => 'booking.rejected',
            'booking_id' => $booking->id,
            'admin_id'   => Auth::id(),
            'user_id'    => $booking->user_id,
            'room_id'    => $booking->room_id,
        ]);

        return redirect()->route('admin.bookings.index')->with('success', 'Booking rejected successfully.');
    }
}