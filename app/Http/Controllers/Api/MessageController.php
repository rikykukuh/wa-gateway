<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\ApiClient;
use App\Models\Device;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MessageController extends Controller
{
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
        $scheduledAt = $this->nextScheduleFor($device);
        $message = Message::create([
            'device_id' => $device->id,
            'recipient' => $validated['recipient'],
            'body' => $validated['message'],
            'status' => 'queued',
            'scheduled_at' => $scheduledAt,
        ]);

        return response()->json([
            'message' => 'Pesan masuk antrean pengiriman.',
            'data' => $message->fresh(),
        ], 202);
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
            ->whereIn('status', ['queued', 'processing', 'sent'])
            ->whereDate('created_at', today())
            ->count();

        if ($sentToday >= $client->daily_message_limit) {
            return response()->json([
                'message' => "Batas harian {$client->daily_message_limit} pesan sudah tercapai.",
                'limit' => $client->daily_message_limit,
                'used' => $sentToday,
            ], 429);
        }

        return null;
    }

    private function nextScheduleFor(Device $device): Carbon
    {
        $delay = (int) config('wa-gateway.scheduler.delay_seconds', 30);
        $lastSlot = $device->messages()
            ->whereIn('status', ['queued', 'processing'])
            ->latest('scheduled_at')
            ->value('scheduled_at');
        $base = $lastSlot ? Carbon::parse($lastSlot) : now();

        return $base->max(now())->addSeconds($delay);
    }
}
