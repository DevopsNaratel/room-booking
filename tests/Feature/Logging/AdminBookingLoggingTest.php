<?php

namespace Tests\Feature\Logging;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;

/**
 * @requires extension pdo
 */
class AdminBookingLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo')) {
            $this->markTestSkipped('PDO extension is not available in this environment.');
        }
    }

    public function test_admin_approves_booking_logs_event()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $booking = Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
            'purpose' => 'For approval',
            'status' => 'pending',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Booking approved', \Mockery::on(function ($payload) use ($admin, $booking) {
                return $payload['event'] === 'booking.approved'
                    && $payload['admin_id'] === $admin->id
                    && $payload['booking_id'] === $booking->id;
            }));

        $response = $this->actingAs($admin)->post(route('admin.bookings.approve', $booking));

        $response->assertRedirect(route('admin.bookings.index'));
    }

    public function test_admin_rejects_booking_logs_event()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $booking = Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
            'purpose' => 'For rejection',
            'status' => 'pending',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Booking rejected', \Mockery::on(function ($payload) use ($admin, $booking) {
                return $payload['event'] === 'booking.rejected'
                    && $payload['admin_id'] === $admin->id
                    && $payload['booking_id'] === $booking->id;
            }));

        $response = $this->actingAs($admin)->post(route('admin.bookings.reject', $booking));

        $response->assertRedirect(route('admin.bookings.index'));
    }
}
