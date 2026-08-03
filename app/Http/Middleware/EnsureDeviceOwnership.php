<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->attributes->get('api_client');
        $device = $request->route('device');

        if ($client instanceof ApiClient && $device instanceof Device && $device->api_client_id !== $client->id) {
            abort(404);
        }

        return $next($request);
    }
}
