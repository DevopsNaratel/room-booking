<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Models\Booking; // Import the Booking model
use App\Models\Room;
use Carbon\Carbon; // Import Carbon

// routes/web.php
Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('rooms.index');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Mahasiswa Routes
    Route::middleware(['role:mahasiswa'])->group(function () {
        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::get('/my-bookings', [BookingController::class, 'index'])->name('my-bookings.index');
        Route::get('/bookings/{booking}/edit', [BookingController::class, 'edit'])->name('bookings.edit');
        Route::patch('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
    });

    // Admin Routes
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard/{year?}/{month?}', function ($year = null, $month = null) {
            $date = Carbon::now();
            if ($year && $month) {
                $date->year((int) $year)->month((int) $month);
            }

            $startOfMonth = $date->copy()->startOfMonth()->startOfDay();
            $endOfMonth = $date->copy()->endOfMonth()->endOfDay();

            $bookings = Booking::with(['user', 'room'])
                ->where('status', 'approved')
                ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->where('start_time', '<=', $endOfMonth)
                        ->where('end_time', '>=', $startOfMonth);
                })
                ->get();

            $daysInMonth = $date->daysInMonth;
            $firstDayOfMonth = Carbon::parse($date->format('Y-m-01'));
            $startDayOfWeek = $firstDayOfMonth->dayOfWeekIso; // 1 for Monday, 7 for Sunday

            $calendar = [];
            // Fill leading empty days
            for ($i = 1; $i < $startDayOfWeek; $i++) {
                $calendar[] = null;
            }

            // Fill days of the month
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $calendar[] = $i;
            }

            $bookedDays = [];
            foreach ($bookings as $booking) {
                $bookingStart = $booking->start_time;
                $bookingEnd = $booking->end_time;

                $currentMonthStart = $date->copy()->startOfMonth()->startOfDay();
                $currentMonthEnd = $date->copy()->endOfMonth()->endOfDay();

                // Determine the actual start and end day within the *current calendar month*
                $displayStartDay = max($bookingStart->day, $currentMonthStart->day);
                $displayEndDay = min($bookingEnd->day, $currentMonthEnd->day);

                // Adjust for bookings starting in previous month and ending in current month
                if ($bookingStart->lt($currentMonthStart) && $bookingEnd->month === $date->month) {
                    $displayStartDay = $currentMonthStart->day;
                }
                // Adjust for bookings starting in current month and ending in next month
                if ($bookingEnd->gt($currentMonthEnd) && $bookingStart->month === $date->month) {
                    $displayEndDay = $currentMonthEnd->day;
                }
                // Adjust for bookings spanning across current month entirely
                if ($bookingStart->lt($currentMonthStart) && $bookingEnd->gt($currentMonthEnd)) {
                    $displayStartDay = $currentMonthStart->day;
                    $displayEndDay = $currentMonthEnd->day;
                }


                for ($day = $displayStartDay; $day <= $displayEndDay; $day++) {
                    // Ensure the day is within the current month's bounds
                    if ($day >= 1 && $day <= $daysInMonth) {
                        if (!isset($bookedDays[$day])) {
                            $bookedDays[$day] = [];
                        }
                        $bookedDays[$day][] = $booking;
                    }
                }
            }

            $previousMonthDate = $date->copy()->subMonth();
            $nextMonthDate = $date->copy()->addMonth();

            $totalRooms = Room::count();
            $totalApprovedThisMonth = Booking::where('status', 'approved')
                ->whereMonth('start_time', $date->month)
                ->whereYear('start_time', $date->year)
                ->count();
            $pendingRequestsCount = Booking::where('status', 'pending')->count();
            $pendingBookings = Booking::with(['room', 'user'])
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get();

            return view('admin.dashboard', compact(
                'bookings',
                'calendar',
                'bookedDays',
                'date',
                'previousMonthDate',
                'nextMonthDate',
                'totalRooms',
                'totalApprovedThisMonth',
                'pendingRequestsCount',
                'pendingBookings'
            ));
        })->name('dashboard');

        Route::resource('rooms', AdminRoomController::class);
        Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
        Route::put('/bookings/{booking}/approve', [AdminBookingController::class, 'approve'])->name('bookings.approve');
        Route::put('/bookings/{booking}/reject', [AdminBookingController::class, 'reject'])->name('bookings.reject');
    });
});

require __DIR__ . '/auth.php';