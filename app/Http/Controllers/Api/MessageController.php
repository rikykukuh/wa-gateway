<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\ApiClient;
use App\Models\Device;
use App\Models\Message;
use App\Services\WhatsAppEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class MessageController extends Controller
{
    public function __construct(private readonly WhatsAppEngine $engine) {}

    public function all(Request $request): JsonResponse
    {
        $client = $request->attributes->get('api_client');
        $query = Message::query()
            ->with(['device:id,name,phone_number,api_client_id'])
            ->latest();

        if ($client instanceof ApiClient) {
            $query->whereHas('device', fn ($deviceQuery) => $deviceQuery->where('api_client_id', $client->id));
        }

        return response()->json([
            'data' => $query->paginate(50),
        ]);
    }

    public function index(Device $device): JsonResponse
    {
        return response()->json([
            'data' => $device->messages()->latest()->paginate(25),
        ]);
    }

    public function store(SendMessageRequest $request, Device $device): JsonResponse
    {
        if ($device->status !== 'connected') {
            return response()->json([
                'message' => 'Device belum terhubung. Hubungkan dan scan QR terlebih dahulu.',
            ], 409);
        }

        $limitResponse = $this->enforceClientLimits($request, $device);
        if ($limitResponse) {
            return $limitResponse;
        }

        $validated = $request->validated();
        $message = Message::create([
            'device_id' => $device->id,
            'recipient' => $validated['recipient'],
            'body' => $validated['message'],
            'status' => 'pending',
        ]);

        try {
            $result = $this->engine->sendText(
                $device->id,
                $validated['recipient'],
                $validated['message'],
            );

            $message->update([
                'status' => 'sent',
                'provider_message_id' => $result['messageId'] ?? null,
                'sent_at' => now(),
            ]);

            return response()->json(['data' => $message->fresh()], 201);
        } catch (Throwable $exception) {
            $message->update(['status' => 'failed', 'error' => $exception->getMessage()]);
            report($exception);

            return response()->json([
                'message' => 'Pesan gagal dikirim.',
                'data' => $message->fresh(),
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 502);
        }
    }

    private function enforceClientLimits(Request $request, Device $device): ?JsonResponse
    {
        $client = $request->attributes->get('api_client');
        if (! $client instanceof ApiClient) {
            $client = $device->apiClient;
        }
        if (! $client) {
            return null;
        }

        $sentToday = Message::query()
            ->whereHas('device', fn ($query) => $query->where('api_client_id', $client->id))
            ->whereIn('status', ['pending', 'sent'])
            ->whereDate('created_at', today())
            ->count();

        if ($sentToday >= $client->daily_message_limit) {
            return response()->json([
                'message' => "Batas harian {$client->daily_message_limit} pesan sudah tercapai.",
                'limit' => $client->daily_message_limit,
                'used' => $sentToday,
            ], 429);
        }

        $lastMessage = Message::query()
            ->whereHas('device', fn ($query) => $query->where('api_client_id', $client->id))
            ->where('status', 'sent')
            ->latest('sent_at')
            ->first();

        if ($lastMessage?->sent_at) {
            $elapsed = (int) $lastMessage->sent_at->diffInSeconds(now());
            $retryAfter = max(0, $client->min_delay_seconds - $elapsed);
            if ($retryAfter > 0) {
                return response()->json([
                    'message' => "Tunggu {$retryAfter} detik sebelum mengirim pesan berikutnya.",
                    'retry_after' => $retryAfter,
                ], 429)->header('Retry-After', (string) $retryAfter);
            }
        }

        return null;
    }
}
