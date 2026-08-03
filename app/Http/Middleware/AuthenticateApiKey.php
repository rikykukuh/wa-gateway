<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = (string) config('wa-gateway.api_key');
        $providedKey = (string) ($request->bearerToken() ?: $request->header('X-API-Key'));

        if ($configuredKey !== '' && hash_equals($configuredKey, $providedKey)) {
            $request->attributes->set('api_client', null);

            return $next($request);
        }

        $client = $providedKey === ''
            ? null
            : ApiClient::query()
                ->where('api_key_hash', hash('sha256', $providedKey))
                ->where('is_active', true)
                ->first();

        if (! $client) {
            return response()->json(['message' => 'API key tidak valid atau sudah dinonaktifkan.'], 401);
        }

        $request->attributes->set('api_client', $client);
        $client->forceFill(['last_used_at' => now()])->saveQuietly();

        return $next($request);
    }
}
