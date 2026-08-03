<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceRequest;
use App\Models\ApiClient;
use App\Models\Device;
use App\Services\WhatsAppEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DeviceController extends Controller
{
    public function __construct(private readonly WhatsAppEngine $engine) {}

    public function index(Request $request): JsonResponse
    {
        $client = $request->attributes->get('api_client');
        $query = Device::query();
        if ($client instanceof ApiClient) {
            $query->whereBelongsTo($client);
        }

        return response()->json([
            'data' => $query->latest()->get(),
        ]);
    }

    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $client = $request->attributes->get('api_client');
        $device = Device::create([
            ...$request->validated(),
            'api_client_id' => $client instanceof ApiClient ? $client->id : null,
        ]);

        return response()->json(['data' => $device], 201);
    }

    public function show(Device $device): JsonResponse
    {
        try {
            $state = $this->engine->status($device->id);
            $this->syncState($device, $state);
        } catch (Throwable) {
            // The persisted device remains available while the engine is restarting.
        }

        return response()->json(['data' => $device->fresh()]);
    }

    public function connect(Device $device): JsonResponse
    {
        try {
            $state = $this->engine->connect($device->id);
            $this->syncState($device, $state);

            return response()->json([
                'message' => 'Sesi koneksi dimulai. Scan QR melalui WhatsApp > Perangkat tertaut.',
                'data' => $device->fresh(),
                'qr' => $state['qr'] ?? null,
            ], 202);
        } catch (Throwable $exception) {
            $device->update(['status' => 'error', 'last_error' => $exception->getMessage()]);

            return $this->engineUnavailable($exception);
        }
    }

    public function qr(Device $device): JsonResponse
    {
        try {
            $state = $this->engine->status($device->id);
            $this->syncState($device, $state);

            return response()->json([
                'data' => $device->fresh(),
                'qr' => $state['qr'] ?? null,
            ]);
        } catch (Throwable $exception) {
            return $this->engineUnavailable($exception);
        }
    }

    public function disconnect(Device $device): JsonResponse
    {
        try {
            $state = $this->engine->disconnect($device->id);
            $this->syncState($device, $state);

            return response()->json(['message' => 'Device diputus sementara.', 'data' => $device->fresh()]);
        } catch (Throwable $exception) {
            return $this->engineUnavailable($exception);
        }
    }

    public function logout(Device $device): JsonResponse
    {
        try {
            $state = $this->engine->disconnect($device->id, true);
            $this->syncState($device, $state);

            return response()->json(['message' => 'Device logout dan sesi lokal dihapus.', 'data' => $device->fresh()]);
        } catch (Throwable $exception) {
            return $this->engineUnavailable($exception);
        }
    }

    public function destroy(Device $device): JsonResponse
    {
        try {
            $this->engine->remove($device->id);
        } catch (Throwable $exception) {
            return $this->engineUnavailable($exception);
        }

        $device->delete();

        return response()->json(null, 204);
    }

    private function syncState(Device $device, array $state): void
    {
        $status = $state['status'] ?? 'disconnected';
        $device->update([
            'status' => $status,
            'phone_number' => $state['phoneNumber'] ?? $device->phone_number,
            'last_error' => $state['error'] ?? null,
            'connected_at' => $status === 'connected' ? ($device->connected_at ?? now()) : $device->connected_at,
            'last_seen_at' => $status === 'connected' ? now() : $device->last_seen_at,
        ]);
    }

    private function engineUnavailable(Throwable $exception): JsonResponse
    {
        report($exception);

        return response()->json([
            'message' => 'WhatsApp engine tidak dapat dihubungi.',
            'error' => app()->isLocal() ? $exception->getMessage() : null,
        ], 503);
    }
}
