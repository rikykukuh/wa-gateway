<?php

namespace App\Services;

use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Throwable;

class ScheduledMessageDispatcher
{
    public function __construct(private readonly WhatsAppEngine $engine) {}

    public function dispatchDue(int $limit = 25): array
    {
        $messages = Message::query()
            ->with('device.apiClient')
            ->where('status', 'queued')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get()
            ->unique('device_id'); // never send two messages for one device in a scheduler tick

        $result = ['sent' => 0, 'failed' => 0, 'deferred' => 0, 'paused' => 0];
        foreach ($messages as $message) {
            $outcome = $this->dispatch($message);
            $result[$outcome]++;
        }

        return $result;
    }

    private function dispatch(Message $candidate): string
    {
        $message = DB::transaction(function () use ($candidate) {
            $locked = Message::query()->lockForUpdate()->find($candidate->id);
            if (! $locked || $locked->status !== 'queued' || $locked->scheduled_at?->isFuture()) {
                return null;
            }
            $locked->update([
                'status' => 'processing',
                'attempts' => $locked->attempts + 1,
                'last_attempt_at' => now(),
            ]);

            return $locked->fresh(['device.apiClient']);
        });

        if (! $message) {
            return 'deferred';
        }
        if (! $message->device || $message->device->status !== 'connected') {
            $message->update(['status' => 'queued', 'scheduled_at' => now()->addMinutes(5)]);

            return 'deferred';
        }
        if ($this->limitReached($message)) {
            $message->update(['status' => 'queued', 'scheduled_at' => now()->addHour()]);

            return 'deferred';
        }

        try {
            $response = $this->engine->sendText($message->device_id, $message->recipient, $message->body);
            $message->update([
                'status' => 'sent',
                'provider_message_id' => $response['messageId'] ?? null,
                'error' => null,
                'sent_at' => now(),
            ]);

            return 'sent';
        } catch (Throwable $exception) {
            report($exception);
            if ($this->isRestriction($exception)) {
                Message::query()
                    ->where('device_id', $message->device_id)
                    ->whereIn('status', ['queued', 'processing'])
                    ->update(['status' => 'paused', 'error' => 'Pengiriman dihentikan: akun terdeteksi dibatasi WhatsApp.']);
                $message->device->update(['last_error' => 'Pengiriman otomatis dihentikan karena akun dibatasi WhatsApp.']);

                return 'paused';
            }

            $maxAttempts = (int) config('wa-gateway.scheduler.max_attempts', 3);
            $message->update($message->attempts >= $maxAttempts
                ? ['status' => 'failed', 'error' => $exception->getMessage()]
                : [
                    'status' => 'queued',
                    'error' => $exception->getMessage(),
                    'scheduled_at' => now()->addMinutes(5 * $message->attempts),
                ]);

            return $message->attempts >= $maxAttempts ? 'failed' : 'deferred';
        }
    }

    private function limitReached(Message $message): bool
    {
        $base = Message::query()->where('device_id', $message->device_id)->where('status', 'sent');
        $hourly = (clone $base)->where('sent_at', '>=', now()->subHour())->count();
        $daily = (clone $base)->whereDate('sent_at', today())->count();

        return $hourly >= (int) config('wa-gateway.scheduler.hourly_limit_per_device', 20)
            || $daily >= (int) config('wa-gateway.scheduler.daily_limit_per_device', 60);
    }

    private function isRestriction(Throwable $exception): bool
    {
        $text = strtolower($exception->getMessage());

        return str_contains($text, '463')
            || str_contains($text, 'restricted')
            || str_contains($text, 'reachout')
            || str_contains($text, 'new chat limit')
            || str_contains($text, 'account is blocked');
    }
}
