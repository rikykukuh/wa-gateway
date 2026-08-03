<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Device;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['wa-gateway.api_key' => 'master-test-key']);
    }

    public function test_master_can_create_a_client_and_plain_key_is_only_returned_once(): void
    {
        $response = $this->withToken('master-test-key')->postJson('/api/v1/clients', [
            'name' => 'Toko Bandung',
            'email' => 'bandung@example.com',
            'daily_message_limit' => 60,
            'min_delay_seconds' => 30,
        ])->assertCreated();

        $plainKey = $response->json('api_key');
        $this->assertStringStartsWith('wag_live_', $plainKey);
        $this->assertDatabaseHas('api_clients', [
            'email' => 'bandung@example.com',
            'api_key_hash' => hash('sha256', $plainKey),
        ]);
        $this->assertDatabaseMissing('api_clients', ['api_key_hash' => $plainKey]);

        $this->withToken('master-test-key')
            ->getJson('/api/v1/clients')
            ->assertOk()
            ->assertJsonMissing(['api_key' => $plainKey]);
    }

    public function test_client_only_sees_and_accesses_its_own_devices(): void
    {
        [$clientA, $keyA] = $this->client('Client A', 'a@example.com');
        [$clientB] = $this->client('Client B', 'b@example.com');
        $foreignDevice = Device::create([
            'api_client_id' => $clientB->id,
            'name' => 'Milik B',
        ]);

        $ownDevice = $this->withToken($keyA)
            ->postJson('/api/v1/devices', ['name' => 'Milik A'])
            ->assertCreated()
            ->assertJsonPath('data.api_client_id', $clientA->id)
            ->json('data');

        $this->withToken($keyA)
            ->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownDevice['id']);

        $this->withToken($keyA)
            ->getJson("/api/v1/devices/{$foreignDevice->id}")
            ->assertNotFound();
    }

    public function test_client_key_cannot_manage_other_api_clients_and_can_be_disabled(): void
    {
        [$client, $key] = $this->client('Client A', 'a@example.com');

        $this->withToken($key)->getJson('/api/v1/clients')->assertForbidden();

        $client->update(['is_active' => false]);
        $this->withToken($key)->getJson('/api/v1/devices')->assertUnauthorized();
    }

    public function test_daily_message_limit_is_enforced_across_client_devices(): void
    {
        [$client, $key] = $this->client('Client A', 'a@example.com', 1, 1);
        $firstDevice = Device::create([
            'api_client_id' => $client->id,
            'name' => 'Device 1',
            'status' => 'connected',
        ]);
        $secondDevice = Device::create([
            'api_client_id' => $client->id,
            'name' => 'Device 2',
            'status' => 'connected',
        ]);
        Message::create([
            'device_id' => $firstDevice->id,
            'recipient' => '628111111111',
            'body' => 'Pesan sebelumnya',
            'status' => 'sent',
            'sent_at' => now()->subMinutes(5),
        ]);

        $this->withToken($key)
            ->postJson("/api/v1/devices/{$secondDevice->id}/messages", [
                'recipient' => '628222222222',
                'message' => 'Pesan berikutnya',
            ])
            ->assertTooManyRequests()
            ->assertJsonPath('limit', 1)
            ->assertJsonPath('used', 1);
    }

    public function test_message_history_includes_device_and_is_scoped_to_client(): void
    {
        [$clientA, $keyA] = $this->client('Client A', 'a@example.com');
        [$clientB] = $this->client('Client B', 'b@example.com');
        $deviceA = Device::create(['api_client_id' => $clientA->id, 'name' => 'Sales A']);
        $deviceB = Device::create(['api_client_id' => $clientB->id, 'name' => 'Sales B']);

        Message::create([
            'device_id' => $deviceA->id,
            'recipient' => '628111111111',
            'body' => 'Pesan milik A',
            'status' => 'sent',
        ]);
        Message::create([
            'device_id' => $deviceB->id,
            'recipient' => '628222222222',
            'body' => 'Pesan milik B',
            'status' => 'sent',
        ]);

        $this->withToken($keyA)
            ->getJson('/api/v1/messages')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.body', 'Pesan milik A')
            ->assertJsonPath('data.data.0.device.name', 'Sales A')
            ->assertJsonMissing(['body' => 'Pesan milik B']);

        $this->withToken('master-test-key')
            ->getJson('/api/v1/messages')
            ->assertOk()
            ->assertJsonPath('data.total', 2);
    }

    private function client(
        string $name,
        string $email,
        int $dailyLimit = 60,
        int $minimumDelay = 30,
    ): array {
        $key = 'wag_live_'.bin2hex(random_bytes(16));
        $client = ApiClient::create([
            'name' => $name,
            'email' => $email,
            'key_prefix' => substr($key, 0, 17),
            'api_key_hash' => hash('sha256', $key),
            'daily_message_limit' => $dailyLimit,
            'min_delay_seconds' => $minimumDelay,
            'is_active' => true,
        ]);

        return [$client, $key];
    }
}
