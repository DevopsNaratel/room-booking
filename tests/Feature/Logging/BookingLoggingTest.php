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
class BookingLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo')) {
            $this->markTestSkipped('PDO extension is not available in this environment.');
        }
    }

    public function test_booking_creation_logs_event()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Room has been booked', \Mockery::on(function ($payload) {
                return isset($payload['event']) && $payload['event'] === 'room.booked'
                    && isset($payload['room_id']) && isset($payload['start_time']) && isset($payload['end_time']);
            }));

        $user = User::factory()->create();
        $room = Room::factory()->create();

        $response = $this->actingAs($user)->post(route('my-bookings.store'), [
            'room_id' => $room->id,
            'start_time' => now()->addHour()->toDateTimeString(),
            'end_time' => now()->addHours(2)->toDateTimeString(),
            'purpose' => 'Meeting',
        ]);

        $response->assertRedirect(route('my-bookings.index'));
        $this->assertDatabaseHas('bookings', ['user_id' => $user->id, 'room_id' => $room->id]);
    }

    public function test_booking_update_logs_event()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Booking updated', \Mockery::on(function ($payload) {
                return isset($payload['event']) && $payload['event'] === 'booking.updated'
                    && isset($payload['booking_id']);
            }));

        $user = User::factory()->create();
        $room = Room::factory()->create();

        $booking = Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
            'purpose' => 'Initial',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->put(route('my-bookings.update', $booking), [
            'room_id' => $room->id,
            'start_time' => now()->addHours(3)->toDateTimeString(),
            'end_time' => now()->addHours(4)->toDateTimeString(),
            'purpose' => 'Updated',
        ]);

        $response->assertRedirect(route('my-bookings.index'));
    }

    public function test_booking_deletion_logs_event()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Booking deleted', \Mockery::on(function ($payload) {
                return isset($payload['event']) && $payload['event'] === 'booking.deleted'
                    && isset($payload['booking_id']);
            }));

        $user = User::factory()->create();
        $room = Room::factory()->create();

        $booking = Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(2),
            'purpose' => 'To delete',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->delete(route('my-bookings.destroy', $booking));

        $response->assertRedirect(route('my-bookings.index'));
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }
}
