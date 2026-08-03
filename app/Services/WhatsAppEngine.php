<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WhatsAppEngine
{
    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('wa-gateway.engine_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withHeader('X-Engine-Secret', (string) config('wa-gateway.engine_secret'))
            ->timeout(config('wa-gateway.request_timeout'))
            ->connectTimeout(5);
    }

    public function connect(string $deviceId): array
    {
        return $this->client()->post("/devices/{$deviceId}/connect")->throw()->json();
    }

    public function status(string $deviceId): array
    {
        return $this->client()->get("/devices/{$deviceId}")->throw()->json();
    }

    public function disconnect(string $deviceId, bool $logout = false): array
    {
        return $this->client()->post("/devices/{$deviceId}/disconnect", [
            'logout' => $logout,
        ])->throw()->json();
    }

    public function remove(string $deviceId): void
    {
        $this->client()->delete("/devices/{$deviceId}")->throw();
    }

    public function sendText(string $deviceId, string $recipient, string $body): array
    {
        return $this->client()->post("/devices/{$deviceId}/messages", [
            'recipient' => $recipient,
            'body' => $body,
        ])->throw()->json();
    }
}
