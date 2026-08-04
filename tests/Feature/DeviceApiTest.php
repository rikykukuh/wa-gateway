<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeviceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'wa-gateway.api_key' => 'test-api-key',
            'wa-gateway.engine_url' => 'http://engine.test',
            'wa-gateway.engine_secret' => 'engine-secret',
        ]);
    }

    public function test_api_requires_a_valid_key(): void
    {
        $this->getJson('/api/v1/devices')->assertUnauthorized();
    }

    public function test_device_can_be_created_and_connected(): void
    {
        Http::fake([
            'http://engine.test/devices/*/connect' => Http::response([
                'status' => 'qr',
                'qr' => 'sample-qr-value',
                'phoneNumber' => null,
            ], 202),
        ]);

        $device = $this->withToken('test-api-key')
            ->postJson('/api/v1/devices', ['name' => 'Customer Service'])
            ->assertCreated()
            ->json('data');

        $this->withToken('test-api-key')
            ->postJson("/api/v1/devices/{$device['id']}/connect")
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'qr')
            ->assertJsonPath('qr', 'sample-qr-value');
    }

    public function test_connected_device_can_queue_and_scheduler_sends_a_message(): void
    {
        $device = Device::create(['name' => 'Sales', 'status' => 'connected']);

        Http::fake([
            'http://engine.test/devices/*/messages' => Http::response([
                'messageId' => 'ABC123',
                'recipient' => '628123456789@s.whatsapp.net',
            ], 201),
        ]);

        $this->withToken('test-api-key')
            ->postJson("/api/v1/devices/{$device->id}/messages", [
                'recipient' => '+62 812-3456-789',
                'message' => 'Halo dari gateway',
            ])
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'queued');

        $this->assertDatabaseHas('messages', [
            'device_id' => $device->id,
            'recipient' => '628123456789',
            'status' => 'queued',
        ]);

        Message::query()->update(['scheduled_at' => now()->subSecond()]);
        $this->artisan('messages:dispatch')->assertSuccessful();

        $this->assertDatabaseHas('messages', [
            'device_id' => $device->id,
            'status' => 'sent',
            'provider_message_id' => 'ABC123',
        ]);
    }

    public function test_every_message_is_scheduled_thirty_seconds_after_the_previous_slot(): void
    {
        $device = Device::create(['name' => 'Antrean', 'status' => 'connected']);
        $startedAt = now();

        foreach (['Pesan pertama', 'Pesan kedua'] as $body) {
            $this->withToken('test-api-key')->postJson("/api/v1/devices/{$device->id}/messages", [
                'recipient' => '628123456789',
                'message' => $body,
            ])->assertStatus(202);
        }

        $messages = Message::query()->orderBy('scheduled_at')->get();
        $this->assertSame(30, $messages[0]->scheduled_at->timestamp - $startedAt->timestamp);
        $this->assertSame(30, $messages[1]->scheduled_at->timestamp - $messages[0]->scheduled_at->timestamp);
    }
}
