<?php

namespace Tests\Feature\Logging;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Room;

/**
 * @requires extension pdo
 */
class RoomLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo')) {
            $this->markTestSkipped('PDO extension is not available in this environment.');
        }
    }

    public function test_admin_creates_room_logs_event()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Log::shouldReceive('info')
            ->once()
            ->with('Room created', \Mockery::on(function ($payload) {
                return $payload['event'] === 'room.created' && isset($payload['room_id']);
            }));

        $response = $this->actingAs($admin)->post(route('admin.rooms.store'), [
            'name' => 'Test Room',
            'capacity' => 10,
            'facilities' => 'Projector, Whiteboard',
        ]);

        $response->assertRedirect(route('admin.rooms.index'));
    }
}
